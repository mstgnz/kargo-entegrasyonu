<?php

class YurtIci{

    private $soap, $urlMode, $self;
    private $data = [
        "wsUserName" => "YKTEST",
        "wsPassword" => "YK",
        "wsUserLanguage" => "TR",
        "userLanguage" => "TR",
        "payerCustData" => [
            "invCustId" => "",
            "invAddressId" => ""
        ]
    ];
    private $urls = [
        "shipment" => [ // kargo işlmeleri için
            "test" => "http://testwebservices.yurticikargo.com:9090/KOPSWebServices/NgiShipmentInterfaceServices?wsdl",
            "live" => "http://webservices.yurticikargo.com:8080/KOPSWebServices/NgiShipmentInterfaceServices?wsdl"
        ],
        "reference" => [ // kargo sorgulama işlemleri için
            "test" => "http://testwebservices.yurticikargo.com:9090/KOPSWebServices/WsReportWithReferenceServices?wsdl",
            "live" => "http://webservices.yurticikargo.com:8080/KOPSWebServices/WsReportWithReferenceServices?wsdl"
        ],
        "self" => [ // Kendi anlaşması olan müşteriler için
            "test" => "http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices?wsdl",
            "live" => "http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl"
        ]
    ];
    private $neccessary = [
        "shipmentData" => [ // genel bilgiler
            "ngiDocumentKey", "cargoType", "totalCargoCount", "totalDesi", "totalWeight", "personGiver", "productCode"
        ],
        "docCargoDataArray"=>[ // her bir ürün bilgisi - parça bilgisi
            "ngiCargoKey","cargoType","cargoDesi","cargoWeight","weightUnit","cargoCount","width","height","length","dimensionsUnit"
        ],
        "XSenderCustAddress" => [ // gönderici bilgileri
            "senderCustName", "senderAddress", "cityId", "townName", "senderMobilePhone", "senderEmailAddress"
        ],
        "XConsigneeCustAddress" => [ // alıcı bilgileri
            "consigneeCustName", "consigneeAddress", "cityId", "townName", "consigneeMobilePhone", "consigneeEmailAddress"
        ],
        "codData"=>[
            "ttInvoiceAmount"=>"","ttDocumentId"=>"","ttCollectionType"=>"","ttDocumentSaveType"=>"","dcSelectedCredit"=>"","dcCreditRule"=>""
        ],
        "payerCustData"=>[ // Yurtiçikargo sisteminde kayıtlı ödeyecek Müşteri kodudur.
            "invCustId"=>"","invAddressId"=>""
        ],
        "updateDesiWeightParamsVO" => [ // güncelleme işlemleri
            "ngiCargoKey", "ngiDocumentKey", "cancellationDescription"
        ],
        "shipmentParamsData" => [ // kargo sorgulama
            "ngiCargoKey"
        ],
        "EndOfCustParamsVO" => [ // kargo takip
            "fieldName", "fieldValueArray"
        ],
        "createShipment" => [
            "cargoKey","invoiceKey","receiverCustName","receiverAddress","receiverPhone1","cityName","townName","waybillNo"
        ],
        "queryShipment" => []
    ];

    private $unneccessary = [
        "shipmentData" => [
            "description"=>"","selectedArrivalUnitId"=>"","selectedArrivalTransferUnitId"=>""
        ],
        "XSenderCustAddress"=>[
            "senderPhone"=>"","senderCustReferenceId"=>"","senderAddressReferenceId"=>"","senderAdditionalInfo"=>"","latitude"=>"","longitude"=>""
        ],
        "XConsigneeCustAddress"=>[
            "consigneePhone"=>"","consigneeCustReferenceId"=>"","consigneeAddressReferenceId"=>"","consigneeAdditionalInfo"=>"","latitude"=>"","longitude"=>""
        ],
        "complementaryProductDataArray" => [
            "complementaryProductCode"=>""
        ],
        "specialFieldDataArray"=>[
            "specialFieldName"=>"","specialFieldValue"=>""
        ],
        "EndofXSenderCustAddress" => [],
        "shipmentParamsData" => [
            "ngiDocumentKey"=>"", "specialFieldName"=>"", "specialFieldValue"=>""
        ],
        "custParamsVO" => [
            "invCustIdArray"=>"", "senderCustIdArray"=>"", "receiverCustIdArray"=>""
        ],
        "EndOfCustParamsVO" => [
            "docIdArray"=>"", "startDate"=>"", "endDate"=>"", "dateParamType"=>"", "withCargoLifecycle"=>"1"
        ],
        "createShipment" => ["taxOfficeId"=>"", "cargoCount"=>1],
        "queryShipment" => []
    ];

    function __construct($username=false, $password=false, $custId=false, $urlMode="test", $self="shipment"){
        if($username && $password && $custId){
            $this->data["wsUserName"] = $username;
            $this->data["wsPassword"] = $password;
            $this->data["payerCustData"]['invCustId'] = $custId;
            $this->unneccessary['custParamsVO']['invCustIdArray'] = $custId;
            $this->unneccessary['shipmentParamsData']['projectCustIdArray'] = $custId;
        }
        $this->urlMode = $urlMode;
        // Kobisi ve mağaza anlaşmasına göre istek atılacak url ataması ayarlanıyor
        $this->self = $self=="self" ? "self" : "shipment";
        $this->soap = new \SoapClient($this->urls[$this->self][$this->urlMode], array('trace' => true));
    }

    // KOBİSİ ANLAŞMASINI KULLANAN MAĞAZALAR İÇİN KULLANILACAK METHODLAR
    public function createNgiShipmentWithAddress($shipmentData=[], $docCargoDataArray=[], $XSenderCustAddress=[], $XConsigneeCustAddress=[], $codData=[]){
        if(!$this->arrayCheck($shipmentData, "shipmentData") && !$this->arrayCheck($docCargoDataArray, "docCargoDataArray") && !$this->arrayCheck($XSenderCustAddress, "XSenderCustAddress") && !$this->arrayCheck($XConsigneeCustAddress, "XConsigneeCustAddress")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data["shipmentData"] = array_merge($shipmentData, $this->unneccessary['shipmentData']);
        $this->data["shipmentData"]["docCargoDataArray"] = $docCargoDataArray;
        $this->data["shipmentData"]["specialFieldDataArray"] = $this->unneccessary['specialFieldDataArray'];
        $this->data["shipmentData"]["complementaryProductDataArray"] = $this->unneccessary['complementaryProductDataArray'];
        $this->data["shipmentData"]["codData"] = $codData;
        $this->data["XSenderCustAddress"] = array_merge($XSenderCustAddress, $this->unneccessary['XSenderCustAddress']);
        $this->data["XConsigneeCustAddress"] = array_merge($XConsigneeCustAddress, $this->unneccessary['XConsigneeCustAddress']);
        try{
            $res = $this->soap->createNgiShipmentWithAddress($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Taşıma irsaliyesinin sevk edilmeden önce iptaline, sevk edildikten sonra da iade isteğinin oluşturulmasına imkan verir.
    public function cancelNgiShipment($updateDesiWeightParamsVO){
        if(!$this->arrayCheck($updateDesiWeightParamsVO, "updateDesiWeightParamsVO")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data = array_merge($this->data, $updateDesiWeightParamsVO);
        try{
            $res = $this->soap->cancelNgiShipment($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Taşıma irsaliyesi bilgilerinin müşteri kargo referans no (ngiCargoKey) değeri ile sorgulanabilmesini sağlar.
    public function selectShipment($shipmentParamsData){
        if(!$this->arrayCheck($shipmentParamsData, "shipmentParamsData")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data["shipmentParamsData"] = array_merge($shipmentParamsData, $this->unneccessary['shipmentParamsData']);
        try{
            $res = $this->soap->selectShipment($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Fiziken sevk edilmiş, Taşıma irsaliyesi / gönderilerin durumu ve kargo hareketlerinin sorgulanmasına imkan verir.
    public function listInvDocumentInterfaceByReference($custParamsVO=[], $EndOfCustParamsVO=[]){
        if(!$this->arrayCheck($EndOfCustParamsVO, "EndOfCustParamsVO")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $data["userName"] = $this->data['wsUserName'];
        $data["password"] = $this->data['wsPassword'];
        $data["language"] = $this->data['wsUserLanguage'];
        $data["custParamsVO"] = array_merge($custParamsVO, $this->unneccessary['custParamsVO']);
        $data = array_merge($data, $EndOfCustParamsVO, $this->unneccessary['EndOfCustParamsVO']);
        try{
            $this->soap = new \SoapClient($this->urls['reference'][$this->urlMode]);
            $res = $this->soap->listInvDocumentInterfaceByReference($data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // KENDİ ANLAŞMASINI KULLANAN MAĞAZALAR İÇİN KULLANILACAK METHODLAR
    // Kargo oluştur
    public function createShipment($createShipment=[]){
        if(!$this->arrayCheck($createShipment, "createShipment")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data['ShippingOrderVO'] = array_merge($createShipment, $this->unneccessary['createShipment']);
        try{
            $res = $this->soap->createShipment($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Kargo iptali
    public function cancelShipment($saleID=false){
        if(!$saleID){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data['cargoKeys'] = $saleID;
        try{
            $res = $this->soap->cancelShipment($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Kargom nerede
    public function queryShipment($saleID=false){
        if(!$saleID){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data['keys'] = $saleID;
        $this->data['keyType'] = 0;
        $this->data['addHistoricalData'] = true;
        $this->data['onlyTracking'] = true;
        $this->data['wsLanguage'] = "TR";
        try{
            $res = $this->soap->queryShipment($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    private function arrayCheck($args, $key){
		$result = false;
		if(is_array($args)){
			foreach ($this->neccessary[$key] as $value) {
				$result = array_key_exists($value, $args);
			}
		}
		return $result;
    }

}
