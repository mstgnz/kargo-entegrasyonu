<?php

require_once ROOT . DS . APP_DIR . DS . 'Vendor' . DS . 'Cargo'. DS .'Aras'. DS .'ArasKargo.php';
require_once ROOT . DS . APP_DIR . DS . 'Vendor' . DS . 'Cargo'. DS .'Yurtici.php';
require_once ROOT . DS . APP_DIR . DS . 'Vendor' . DS . 'Cargo'. DS .'Mng.php';
require_once ROOT . DS . APP_DIR . DS . 'Vendor' . DS . 'Cargo'. DS .'Ups.php';
require_once ROOT . DS . APP_DIR . DS . 'Vendor' . DS . 'Cargo'. DS .'UpsYd.php';

class CargoComponent extends Component{
    
    private $db, $companyId, $controller, $sale_id, $data, $cargo, $customer_id, $cargoId, $sender, $contract, $status;
	
	public function __construct(ComponentCollection $collection, array $settings){
		$this->controller = $collection->getController();
		parent::__construct($collection, $settings);
	}

	public function startup(Controller $controller){
		parent::startup($controller);
		$this->db = $this->controller->db;
		$this->companyId  = $this->controller->companyId;
		$this->company  = $this->controller->company;
	}

    public function start($sale_id){
        $this->sale_id = $sale_id;
        if($this->controller->request->is('post')){
            // gönderici bilgileri
            $this->sender = $this->db->query("SELECT * FROM cargo_senders cs WHERE company_id=".$this->companyId);
            // post datası
            $this->data = $this->controller->request->data;
            $this->customer_id = isset($this->data['customer_id']) ? $this->data['customer_id'] : false;
			$this->cargoId = !empty($this->data['cargoID']) ? $this->data['cargoID'] : false;
			$check = isset($this->data['type']) && $this->data['type']=="cargo" ? true : false;
			$where = isset($this->data['type']) && $this->data['type']=="where" ? true : false;
			$cancel = isset($this->data['type']) && $this->data['type']=="cancel" ? true : false;
			$sablon = isset($this->data['type']) && $this->data['type']=="sablon" ? true : false;
			$this->status = isset($this->data['status']) ? $this->data['status'] : false;
			unset($this->data['type']);
			unset($this->data['cargoID']);
            unset($this->data['status']);
            // Kargo Sınıfını Başlat
            $this->newCargo();
            // Kargo Sınıfı Başlatıldıysa İşlemlere Devam et
            if($this->cargo){
                // Kargo işlemini yap
                if($check && $this->status && !$where) $this->cargoPost();
                // Kargom Nerede
                if($where && !$check) $this->cargoWhere();
                // Kargo İptal
                if($cancel && !$check) $this->cargoCancel();
                // Kargo Şablon
                if($sablon) $this->cargoBarkod();
            }
        }
        $this->get();
    }

    // GET
    private function get(){
        $sale_id = $this->sale_id;
        // kargo entegrasyonu var mı ?
		$cargos = $this->db->query("SELECT c.* FROM company_cargo cc LEFT JOIN cargos c ON c.id=cc.cargo_id WHERE cc.is_active=1 AND cc.company_id=".$this->companyId);
		$cargo_products = $this->db->query("SELECT cp.id, cp.sale_id, cp.tracking_number, cp.barkod_link, c.id, c.name, c.slug FROM cargo_products cp LEFT JOIN cargos c ON c.id=cp.cargo_id WHERE cp.sale_id=$sale_id AND cp.status=1 AND cp.company_id=".$this->companyId);
		$this->controller->set('cargos', $cargos);
		$this->controller->set('cargo_products', $cargo_products);
    }

    // Kargo ID'ye göre bilgileri çek ve Kargo entegrasyon sınıfını başlat
    private function newCargo(){
        if($this->cargoId){
            // mağaza anlaşması select
            $self = $this->db->query("SELECT * FROM company_cargo cc WHERE is_active=1 AND company_id=".$this->companyId." AND cargo_id=$this->cargoId");
            // kobisi anlaşması select
            $kobisi = $this->db->query("SELECT * FROM company_cargo cc WHERE is_active=1 AND company_id=1 AND cargo_id=$this->cargoId");
            if($self[0]['cc']['contract']=="self"){
                $cUsername = $self[0]['cc']['username'];
                $cPassword = $self[0]['cc']['password'];
                $custID = $self[0]['cc']['cust_id'];
                $accessKey = $self[0]['cc']['access_key'];
                $this->contract = "self";
            }else{
                $cUsername = $kobisi[0]['cc']['username'];
                $cPassword = $kobisi[0]['cc']['password'];
                $custID = $kobisi[0]['cc']['cust_id'];
                $accessKey = $kobisi[0]['cc']['access_key'];
                $this->contract = "kobisi";
            }
            if($this->cargoId==1){ $this->cargo = new ArasKargo($cUsername, $cPassword,$custID, "setUrl"); }
            if($this->cargoId==5){ $this->cargo = new Mng($cUsername, $cPassword, "live"); }
            if($this->cargoId==10){ $this->cargo = new YurtIci($cUsername, $cPassword, $custID, "live", $this->contract); }
            if($this->cargoId==9){ $this->cargo = new Ups($cUsername, $cPassword, $custID); }
            if($this->cargoId==8){ $this->cargo = new UpsYd($cUsername, $cPassword, $accessKey, $custID); }
        }
    }

    // Kargo işlemini başlat
    private function cargoPost(){
        $success = false;
        $tracking_number = $this->sale_id;
        $barkod_link = "";
        $barkod_string = "";
        $request = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        // Aras kargo post
        if($this->cargoId==1){
            if($this->contract=="self"){
                $result = $this->cargo->SetOrder($this->data);
                if($result['res']->SetOrderResult->OrderResultInfo->ResultCode==0){
                    $success = true;
                    $tracking_number = $result['res']->SetOrderResult->OrderResultInfo->InvoiceKey;
                    $result = $this->sale_id.' nolu ürün için Aras Kargo işlemi gerçekleştirildi.';
                    // Aras gönderi no için queue insert - cron shell
                    $this->controller->Queue->create();
                    $this->controller->Queue->save([
                        "company_id"=>$this->companyId,
                        "queue"=>"aras",
                        "payload" => json_encode(["sale_id"=>$this->sale_id,"customer_id"=>$this->customer_id]),
                        "callable_method" => "Cargo",
                        "run_period" => 86400,
                        "create_date" => Date('Y-m-d H:i:s')
                    ]);
                    $this->controller->Queue->clear();
                }else{
                    $result = $result['res']->SetOrderResult->OrderResultInfo;
                }
            }else{
                $result = "kobisi.com'un Aras anlaşması bulunmuyor.";
            }
        }
        // MNG kargo post
        if($this->cargoId==5){
            if($this->contract=="self"){
                $result = $this->cargo->SiparisGirisiDetayliV3($this->data);
                if($result['res']->SiparisGirisiDetayliV3Result==1){
                    $success = true;
                    $tracking_number = $this->data['pChSiparisNo'];
                    $result = $this->sale_id.' nolu ürün için Mng Kargo işlemi gerçekleştirildi.';
                    // Mng gönderi no için queue insert - cron shell
                    $this->controller->Queue->create();
                    $this->controller->Queue->save([
                        "company_id"=>$this->companyId,
                        "queue"=>"mng",
                        "payload" => json_encode(["sale_id"=>$this->sale_id,"customer_id"=>$this->customer_id]),
                        "callable_method" => "Cargo",
                        "run_period" => 86400,
                        "create_date" => Date('Y-m-d H:i:s')
                    ]);
                    $this->controller->Queue->clear();
                }else{
                    $result = $result['res']->SiparisGirisiDetayliV3Result;
                }
            }else{
                $result = "kobisi.com'un MNG anlaşması bulunmuyor.";
            }
        }
        // Yurtiçi kargo post
        if($this->cargoId==10){
            if($this->contract=="self"){
                $createShipment = [
                    "cargoKey" => $this->sale_id,
                    "invoiceKey" => $this->sale_id,
                    "waybillNo" => $this->sale_id,
                    "receiverCustName" => $this->data["XConsigneeCustAddress"]["consigneeCustName"],
                    "receiverAddress" => $this->data["XConsigneeCustAddress"]["consigneeAddress"],
                    "receiverPhone1" => $this->data["XConsigneeCustAddress"]["consigneeMobilePhone"],
                    "cityName" => $this->data["XConsigneeCustAddress"]["cityId"],
                    "townName" => $this->data["XConsigneeCustAddress"]["townName"],
                    "ttDocumentId" => "",
                    "dcSelectedCredit" => "",
                    "dcCreditRule" => ""
                ];
                if($this->status%10==3 || $this->status%10==4){ // kapıda ödeme varsa set edilmeli yoksa hata alıyor
                    $createShipment["ttCollectionType"] = $this->data['codData']['ttCollectionType'];
                    $createShipment["ttDocumentId"] = $this->data['codData']['ttDocumentId'];
                    $createShipment["ttInvoiceAmount"] = $this->data['codData']['ttInvoiceAmount'];
                    $createShipment["ttDocumentSaveType"] = $this->data['codData']['ttDocumentSaveType'];
                    $createShipment["dcSelectedCredit"] = $this->data['codData']['dcSelectedCredit'];
                    $createShipment["dcCreditRule"] = $this->data['codData']['dcCreditRule'];
                }
                $result = $this->cargo->createShipment($createShipment);
                if($result["res"]->ShippingOrderResultVO->outFlag==0){
                    $success = true;
                    $tracking_number = $this->data["shipmentData"]["ngiDocumentKey"];
                    $result = $this->sale_id.' nolu ürün için Yurtiçi Kargo işlemi gerçekleştirildi.';
                    // Yurtiçi kargo mail
                    $this->controller->SaleHelper->sendSuccessMail($this->sale_id, 'sale_cargo_sent');
                }else{
                    $result = $result["res"]->ShippingOrderResultVO->outResult;
                }
            }else{ // Kobisi anlaşması ise
                $this->data["XSenderCustAddress"]["senderCustName"] = $this->sender[0]["cs"]["fullname"];
                $this->data["XSenderCustAddress"]["senderAddress"] = $this->sender[0]["cs"]["address"];
                $this->data["XSenderCustAddress"]["cityId"] = $this->sender[0]["cs"]["city_id"];
                $this->data["XSenderCustAddress"]["townName"] = $this->sender[0]["cs"]["district_name"];
                $this->data["XSenderCustAddress"]["senderMobilePhone"] = $this->sender[0]["cs"]["phone"];
                $this->data["XSenderCustAddress"]["senderEmailAddress"] = $this->sender[0]["cs"]["email"];
                $result = $this->cargo->createNgiShipmentWithAddress($this->data["shipmentData"], $this->data["docCargoDataArray"], $this->data["XSenderCustAddress"], $this->data["XConsigneeCustAddress"], $this->data['codData']);
                if($result["res"]->XShipmentDataResponse->outFlag==0){
                    $success = true;
                    $tracking_number = $this->data["shipmentData"]["ngiDocumentKey"];
                    $result = $this->sale_id.' nolu ürün için Yurtiçi Kargo işlemi gerçekleştirildi.';
                    // Yurtiçi kargo mail
                    $this->controller->SaleHelper->sendSuccessMail($this->sale_id, 'sale_cargo_sent');
                }else{
                    $result = $result["res"]->XShipmentDataResponse->outResult;
                }
            }
        }
        // Ups kargo post
        if($this->cargoId==9){
            if($this->contract=="self"){
                $senderCitycode = $this->sender[0]["cs"]["city_id"];
                $senderDistrictName = $this->sender[0]["cs"]["district_name"];
                $areaCode = $this->db->query("SELECT ups_district_id FROM ups_districts WHERE ups_city_id=$senderCitycode AND name='$senderDistrictName'");
                $this->data["ShipperAreaCode"] = $areaCode[0]['ups_districts']['ups_district_id'];
                $this->data["ShipperName"] = $this->sender[0]["cs"]["fullname"];
                $this->data["ShipperAddress"] = $this->sender[0]["cs"]["address"];
                $this->data["ShipperCityCode"] = $senderCitycode;
                $this->data["ShipperMobilePhoneNumber"] = $this->sender[0]["cs"]["phone"];
                $this->data["ShipperEMail"] = $this->sender[0]["cs"]["email"];
                $self = $this->db->query("SELECT * FROM company_cargo cc WHERE is_active=1 AND company_id=".$this->companyId." AND cargo_id=$this->cargoId");
                $this->data["ShipperAccountNumber"] = $self[0]["cc"]["cust_id"];
                $reciverDistrictName = $this->data['reciverDistrictName'];
                $cityCode = $this->data['ConsigneeCityCode'];
                $areaCode = $this->db->query("SELECT ups_district_id FROM ups_districts WHERE ups_city_id=$cityCode AND name='$reciverDistrictName'");
                $this->data['ConsigneeAreaCode'] = $areaCode[0]['ups_districts']['ups_district_id'];
                unset($this->data['reciverDistrictName']);
                unset($this->data['customer_id']);
                $result = $this->cargo->CreateShipment_Type2($this->data);
                if($result["status"]=="success"){
                    $success = true;
                    $tracking_number = $result["res"]->CreateShipment_Type2Result->ShipmentNo;
                    $barkod_link = $result["res"]->CreateShipment_Type2Result->LinkForLabelPrinting;
                    $barkod_string = $result["res"]->CreateShipment_Type2Result->BarkodArrayPng->string;
                    $result = $this->sale_id.' nolu ürün için Ups Kargo işlemi gerçekleştirildi.';
                    // Ups kargo mail
                    $this->controller->SaleHelper->sendSuccessMail($this->sale_id, 'sale_cargo_sent');
                }else{
                    $result = $result["res"];
                }
            }else{
                $result = "kobisi.com'un UPS anlaşması bulunmuyor.";
            }
        }
        // Ups Yurtdışı
        if($this->cargoId==8){
            $this->data["shipperFullName"] = $this->sender[0]["cs"]["fullname"];
            $this->data["shipperAddress"] = $this->sender[0]["cs"]["address"];
            $this->data["shipperCityName"] = $this->sender[0]["cs"]["city_name"];
            $this->data["shipperDistrictName"] = $this->sender[0]["cs"]["district_name"];
            $this->data["shipperPhone"] = $this->sender[0]["cs"]["phone"];
            $this->data["shipperEmail"] = $this->sender[0]["cs"]["email"];
            $result = $this->cargo->createShipment($this->data);
            if($result['Response']['ResponseStatusCode']){
                $success = true;
                $tracking_number = $result['ShipmentResults']['PackageResults']['TrackingNumber'];
                $barkod_string = $result['ShipmentResults']['PackageResults']['LabelImage']['GraphicImage'];
                $result = $this->sale_id.' nolu ürün için Ups Yurtdışı Kargo işlemi gerçekleştirildi.';
            }else{
                $result = $result['Response']['Error']['ErrorDescription'];
            }
        }
        // Sipariş işlem güncellemesi
        if($success){
            $this->db->query("UPDATE sales SET `status`=$this->status, tracking_number='$tracking_number' WHERE company_id=".$this->companyId." AND id=".$this->sale_id);
            $checkSaleId = $this->db->query("SELECT * FROM cargo_products WHERE sale_id=$this->sale_id AND company_id=".$this->companyId);
            if(!empty($checkSaleId)){ // kobisi entegrasyonu kullanıldığında api tarafında sale_id ile insert yapılıyor o yüzden update işlemi yapılıyor. 
                $this->db->query("UPDATE cargo_products SET `status`=1, cargo_id=$this->cargoId, request='$request', tracking_number='$tracking_number', barkod_link='$barkod_link', barkod_string='$barkod_string' WHERE company_id=".$this->companyId." AND sale_id=".$this->sale_id);
            }else{
                $this->db->query("INSERT INTO cargo_products (sale_id, cargo_id, company_id, tracking_number, request, `status`, barkod_link, barkod_string) VALUES ($this->sale_id, $this->cargoId, ".$this->companyId.", '$tracking_number', '$request', 1, '$barkod_link', '$barkod_string')");
            }
            $this->controller->Session->setFlash('Başarılı, '.$result, 'flash_success');
        }else{
            $this->controller->Session->setFlash('Hata!, '.$result, 'flash_error');
        }
        $this->controller->redirect('/sale/detail/'.$this->sale_id);
    }

    // Kargom Nerede
    private function cargoWhere(){
        $this->controller->autoRender = false;
        $tracking_number = $this->data['tracking_number'];
        // Aras Kargo
        if($this->cargoId==1){
            echo '<a href="http://kargotakip.araskargo.com.tr/mainpage.aspx?code='.$this->data['tracking_number'].'" target="_blank">Tıklayınız</a>';exit;
        }
        // Mng Kargo
        if($this->cargoId==5){
            $result = $this->cargo->KargoBilgileriByReferans($this->sale_id);
            if(empty($result['res']->pWsError)){
                $xml = $result['res']->KargoBilgileriByReferansResult->any;
                $array = [];
                $XSDDOC = new \DOMDocument(); 
                $XSDDOC->preserveWhiteSpace = false; 
                if ($XSDDOC->loadXML($xml)) { 
                    $xsdpath = new \DOMXPath($XSDDOC); 
                    $attributeNodes = $xsdpath->query('//Table1')->item(0);
                    foreach ($attributeNodes->childNodes as $attr) { 
                        if($attr->localName=="KARGO_TAKIP_URL"){
                            $array[ $attr->localName ] = '<a href="'.$attr->textContent.'">http://service.mngkargo.com.tr/</a>';
                        }else{
                            $array[ $attr->localName ] = $attr->textContent; 
                        }
                    } 
                    echo json_encode($array);
                }
            }else{
                echo $result['res']->pWsError;
            }
        }
        // Yurtiçi Kargo
        if($this->cargoId==10){
            if($this->contract=="self"){
                $result = $this->cargo->queryShipment($this->sale_id);
                if($result['res']->ShippingDeliveryVO->outFlag==0){
                    echo $result['res']->ShippingDeliveryVO->shippingDeliveryDetailVO->operationMessage;
                }else{
                    echo $result['res']->ShippingDeliveryVO->outResult;
                }
            }else{
                $result = $this->cargo->listInvDocumentInterfaceByReference([],["fieldName"=>3, "fieldValueArray"=>$this->sale_id]);
                if($result['res']->ShippingDataResponseVO->outFlag==0){
                    echo $result['res']->ShippingDataResponseVO->shippingDataDetailVOArray->transactionMessage;
                }else{
                    echo $result['res']->ShippingDataResponseVO;
                }
            }
        }
        // Ups Kargo
        if($this->cargoId==9){
            $waybillNumber = $this->db->query("SELECT * FROM cargo_products cp WHERE sale_id=$this->sale_id AND company_id=".$this->companyId);
            $waybillNumber = $waybillNumber[0]["cp"]["tracking_number"];
            $result = $this->cargo->GetShipmentInfoByTrackingNumber_V1($waybillNumber);
            echo json_encode($result);
        }
        // Ups Kargo Yurtdışı
        if($this->cargoId==8){
            $result = $this->cargo->shipTracking($tracking_number);
            echo json_encode($result);
        }
        exit;
    }

    // Kargo İptal
    private function cargoCancel(){
        $this->controller->autoRender = false;
        $cpID = $this->data['cpID'];
        $sale_id = $this->data['sale_id'];
        $tracking_number = $this->data['tracking_number'];
        $iptal = false;
        // Aras Kargo
        if($this->cargoId==1){
            $result = $this->cargo->CancelDispatch($sale_id);
            if($result['res']->CancelDispatchResult->ResultCode == 0){
                $iptal = true;
                // Kuyruğa eklenen datanın siliniyor
                $queueId = $this->controller->Queue->find('first',[
                    'conditions' => [
                        "company_id"=>$this->companyId,
                        "queue"=>"aras",
                        "payload LIKE" => "%".$sale_id."%",
                        "callable_method" => "cargo"
                    ]
                ]);
                if(!empty($queueId) && $queueId['Queue']['id']){
                    $this->controller->Queue->delete($queueId['Queue']['id']);
                }
                echo ($result['res']->CancelDispatchResult->ResultMessage);
            }else{
                echo ($result['res']->CancelDispatchResult->ResultMessage);
            }
        }
        // Mng Kargo
        if($this->cargoId==5){
            $result = $this->cargo->MusteriSiparisIptal($sale_id);
            if($result['res']->MusteriTeslimatIptalIstegiResult){
                $iptal = true;
                // Kuyruğa eklenen datanın siliniyor
                $queueId = $this->controller->Queue->find('first',[
                    'conditions' => [
                        "company_id"=>$this->companyId,
                        "queue"=>"mng",
                        "payload LIKE" => "%".$sale_id."%",
                        "callable_method" => "cargo"
                    ]
                ]);
                if(!empty($queueId) && $queueId['Queue']['id']){
                    $this->controller->Queue->delete($queueId['Queue']['id']);
                }
                echo json_encode($result['res']->pYapilanIslem);
            }else{
                echo json_encode($result['res']->pWsError);
            }
        }
        // Yurtiçi Kargo
        if($this->cargoId==10){
            if($this->contract=="self"){ // Mağaza Anlaşması
                $result = $this->cargo->cancelShipment($sale_id);
                if($result['res']->ShippingOrderResultVO->outFlag==0){
                    $iptal = true;
                    echo "Kargo iptal işlemi gerçekleştirildi";
                }else{
                    echo $result['res']->ShippingOrderResultVO->outResult;
                }
            }else{ // Kobisi Anlaşması
                $req["ngiCargoKey"] = $sale_id;
                $req["ngiDocumentKey"] = $sale_id;
                $req["cancellationDescription"] = "Kobisi.com";
                $result = $this->cargo->cancelNgiShipment($req);
                if($result['res']->XCancelShipmentResponse->outFlag==0){
                    $iptal = true;
                    echo "Kargo iptal işlemi gerçekleştirildi";
                }else{
                    echo $result['res']->XCancelShipmentResponse->outResult;
                }
            }
        }
        // Ups Kargo
        if($this->cargoId==9){
            $self = $this->db->query("SELECT * FROM company_cargo cc WHERE is_active=1 AND company_id=".$this->companyId." AND cargo_id=$this->cargoId");
            $customerCode = $self[0]["cc"]["cust_id"];
            $waybillNumber = $this->db->query("SELECT * FROM cargo_products cp WHERE sale_id=$sale_id AND company_id=".$this->companyId);
            $waybillNumber = $waybillNumber[0]["cp"]["tracking_number"];
            $result = $this->cargo->Cancel_Shipment_V1($customerCode, $waybillNumber);
            if($result["status"]=="success"){
                $iptal = true;
                echo "Kargo iptal işlemi gerçekleştirildi";
            }else{
                echo "Kargo iptal edilemedi";
            }
        }
        // Ups Kargo Yurtdışı
        if($this->cargoId==8){
            $result = $this->cargo->shipCancel($tracking_number);
            if($result['Response']['ResponseStatusCode']){
                $iptal = true;
                echo "Kargo iptal işlemi gerçekleştirildi";
            }else{
                echo "Kargo iptal edilemedi\n".$result['Response']['Error']['ErrorCode'].' : '.$result['Response']['Error']['ErrorDescription'];
            }
        }
        if($iptal){
            // kobisi entegrasyonu varsa ve iptal ediliyorsa alınan kargo bedeli iade edilmeli
            $this->db->query("UPDATE sales SET `status`=$this->status, tracking_number='' WHERE company_id=".$this->companyId." AND id=".$sale_id);
            $this->db->query("UPDATE cargo_products SET `status`=0 WHERE company_id=".$this->companyId." AND sale_id=".$sale_id);
        }
        exit;
    }

    // Kargo Şablon
    private function cargoBarkod(){
        $companyId = $this->companyId;
        $saleId = $this->sale_id;
        // Ups Yurtdışı
        if($this->cargoId==8){
            $barkod = $this->db->query("SELECT barkod_string FROM cargo_products cp WHERE company_id=$companyId AND sale_id=$saleId AND cargo_id=8");
            echo json_encode(reset($barkod));exit;
        }
        // Adres bilgileri
        $address = $this->db->query("SELECT ca.firstname, ca.lastname, ca.city, ca.district, ca.address, ca.phone, cs.fullname, cs.address, cs.city_name, cs.district_name, cs.phone 
        FROM customer_addresses ca 
        INNER JOIN cargo_senders cs ON cs.company_id=$companyId 
        WHERE ca.id=(SELECT shipping_address_id FROM sales s WHERE s.id=$saleId)");
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->company['name']), '-'));
        $address[0]['logo'] = base64_encode(file_get_contents("https://cdn.kobisi.com/cdn/logo/$companyId/$slug"));
        $address[0]['barkod'] = "";
        // Barkod oluşturma MNG barkod işlemleri için EPL/ZPL formatına göre işlem yapılacak
        // Gönderi no için cron yazılarak KargoBilgileriByReferans sorgu sonucu ile gönderi takip no alınacak master
        if($this->cargoId==5){
            $request = $this->db->query("SELECT request FROM cargo_products cp 
            WHERE company_id=$companyId AND sale_id=$saleId");
            $request = json_decode($request[0]['cp']['request']);
            $parcaList = explode(':',$request->pKargoParcaList);
            $GonderiParca = [
                "GonderiParca" => ['Adet'=>1, 'Desi'=>$parcaList[1], 'Kg'=>$parcaList[0], 'Icerik'=>$parcaList[3]]
            ];
            $barkod = $this->cargo->MNGGonderiBarkod($saleId,$GonderiParca);
            if($barkod['res']->MNGGonderiBarkodResult->IstekBasarili){
                $address[0]['barkod'] = $this->zpl($barkod['res']->MNGGonderiBarkodResult->GonderiBarkods->GonderiBarkodBilgi->BarkodText);
            }
        }
        echo json_encode(reset($address));
        exit;
    }

    // Mng kargo barkod
	private function zpl($zpl){
		//$zpl = "^xa^cfa,50^fo100,100^fdHello World^fs^xz";
		$curl = curl_init();
		// adjust print density (8dpmm), label width (4 inches), label height (6 inches), and label index (0) as necessary
		curl_setopt($curl, CURLOPT_URL, "http://api.labelary.com/v1/printers/8dpmm/labels/4x6/0/");
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $zpl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: image/png")); // omit this line to get PNG images back
		$result = curl_exec($curl);
		$result = base64_encode($result);
		/*if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
			$file = fopen("label.pdf", "w"); // change file name for PNG images
			fwrite($file, $result);
			fclose($file);
		} else {
			print_r("Error: $result");
		}*/
		curl_close($curl);
		return $result;
	}
}
