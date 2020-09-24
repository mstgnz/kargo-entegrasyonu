public function cargo($sale_id){
		if($this->request->is('post')){
			$data = $this->request->data;
			$customer_id = isset($data['customer_id']) ? $data['customer_id'] : false;
			$cargoId = !empty($data['cargoID']) ? $data['cargoID'] : false;
			$check = isset($data['type']) && $data['type']=="cargo" ? true : false;
			$where = isset($data['type']) && $data['type']=="where" ? true : false;
			$cancel = isset($data['type']) && $data['type']=="cancel" ? true : false;
			$sablon = isset($data['type']) && $data['type']=="sablon" ? true : false;
			$status = isset($data['status']) ? $data['status'] : false;
			$tracking_number = $sale_id;
			$request = json_encode($data, JSON_UNESCAPED_UNICODE);
			$result = "";
			$success = false;
			unset($data['type']);
			unset($data['cargoID']);
			unset($data['status']);
			if($customer_id){ unset($data['customer_id']); }
			if($cargoId){
				// mağaza anlaşması select
				$self = $this->db->query("SELECT * FROM company_cargo cc WHERE is_active=1 AND company_id=".$this->companyId." AND cargo_id=$cargoId");
				// kobisi anlaşması select
				$kobisi = $this->db->query("SELECT * FROM company_cargo cc WHERE is_active=1 AND company_id=1 AND cargo_id=$cargoId");
				if($self[0]['cc']['contract']=="self"){
					$cUsername = $self[0]['cc']['username'];
					$cPassword = $self[0]['cc']['password'];
					$custID = $self[0]['cc']['cust_id'];
					$contract = "self";
				}else{
					$cUsername = $kobisi[0]['cc']['username'];
					$cPassword = $kobisi[0]['cc']['password'];
					$custID = $kobisi[0]['cc']['cust_id'];
					$contract = "kobisi";
				}
				if($cargoId==5){ $cargo = new Mng($cUsername, $cPassword, "live"); }
				if($cargoId==10){ $cargo = new YurtIci($cUsername, $cPassword, $custID, "live", $contract); }
			}
			if($check && $cargoId && $status && !$where){
				$sender = $this->db->query("SELECT * FROM cargo_senders cs WHERE company_id=".$this->companyId);
				// MNG kargo post
				if($cargoId==5){
					if($contract=="self"){
						$result = $cargo->SiparisGirisiDetayliV3($data);
						if($result['res']->SiparisGirisiDetayliV3Result==1){
							$success = true;
							$tracking_number = $data['pChSiparisNo'];
							$result = $sale_id.' nolu ürün için Mng Kargo işlemi gerçekleştirildi.';
							// Mng gönderi no için queue insert - cron shell
							$this->Queue->create();
							$this->Queue->save([
								"company_id"=>$this->companyId,
								"queue"=>"mng",
								"payload" => json_encode(["sale_id"=>$sale_id,"customer_id"=>$customer_id]),
								"callable_method" => "Cargo",
								"run_period" => 86400,
								"create_date" => Date('Y-m-d H:i:s')
							]);
							$this->Queue->clear();
						}else{
							$result = $result['res']->SiparisGirisiDetayliV3Result;
						}
					}else{
						$result = "kobisi.com'un MNG anlaşması bulunmuyor.";
					}
				}
				// Yurtiçi kargo post
				if($cargoId==10){
					if($contract=="self"){
						$createShipment = [
							"cargoKey" => $sale_id,
							"invoiceKey" => $sale_id,
							"waybillNo" => $sale_id,
							"receiverCustName" => $data["XConsigneeCustAddress"]["consigneeCustName"],
							"receiverAddress" => $data["XConsigneeCustAddress"]["consigneeAddress"],
							"receiverPhone1" => $data["XConsigneeCustAddress"]["consigneeMobilePhone"],
							"cityName" => $data["XConsigneeCustAddress"]["cityId"],
							"townName" => $data["XConsigneeCustAddress"]["townName"],
							"ttCollectionType" => $data['codData']['ttCollectionType'],
							"ttInvoiceAmount" => $data['codData']['ttInvoiceAmount'],
							"ttDocumentId" => $data['codData']['ttDocumentId'],
							"ttDocumentSaveType" => $data['codData']['ttDocumentSaveType'],
							"dcSelectedCredit" => $data['codData']['dcSelectedCredit'],
							"dcCreditRule" => $data['codData']['dcCreditRule']
						];
						$result = $cargo->createShipment($createShipment);
						if($result["res"]->ShippingOrderResultVO->outFlag==0){
							$success = true;
							$tracking_number = $data["shipmentData"]["ngiDocumentKey"];
							$result = $sale_id.' nolu ürün için Yurtiçi Kargo işlemi gerçekleştirildi.';
							// Yurtiçi kargo mail
							$this->SaleHelper->sendSuccessMail($sale_id, 'sale_cargo_sent');
						}else{
							$result = $result["res"]->ShippingOrderResultVO->outResult;
						}
					}else{
						$data["XSenderCustAddress"]["senderCustName"] = $sender[0]["cs"]["fullname"];
						$data["XSenderCustAddress"]["senderAddress"] = $sender[0]["cs"]["address"];
						$data["XSenderCustAddress"]["cityId"] = $sender[0]["cs"]["city_id"];
						$data["XSenderCustAddress"]["townName"] = $sender[0]["cs"]["district_name"];
						$data["XSenderCustAddress"]["senderMobilePhone"] = $sender[0]["cs"]["phone"];
						$data["XSenderCustAddress"]["senderEmailAddress"] = $sender[0]["cs"]["email"];
						$result = $cargo->createNgiShipmentWithAddress($data["shipmentData"], $data["docCargoDataArray"], $data["XSenderCustAddress"], $data["XConsigneeCustAddress"], $data['codData']);
						if($result["res"]->XShipmentDataResponse->outFlag==0){
							$success = true;
							$tracking_number = $data["shipmentData"]["ngiDocumentKey"];
							$result = $sale_id.' nolu ürün için Yurtiçi Kargo işlemi gerçekleştirildi.';
							// Yurtiçi kargo mail
							$this->SaleHelper->sendSuccessMail($sale_id, 'sale_cargo_sent');
						}else{
							$result = $result["res"]->XShipmentDataResponse->outResult;
						}
					}
				}
				// Sipariş işlem güncellemesi
				if($success){
					$this->db->query("UPDATE sales SET `status`=$status, tracking_number=$tracking_number WHERE company_id=".$this->companyId." AND id=".$sale_id);
					$checkSaleId = $this->db->query("SELECT * FROM cargo_products WHERE sale_id=$sale_id AND company_id=".$this->companyId);
					if(!empty($checkSaleId)){ // kobisi entegrasyonu kullanıldığında api tarafında sale_id ile insert yapılıyor o yüzden update işlemi yapılıyor. 
						$this->db->query("UPDATE cargo_products SET `status`=1, cargo_id=$cargoId, request='$request', tracking_number='$sale_id' WHERE company_id=".$this->companyId." AND sale_id=".$sale_id);
					}else{
						$this->db->query("INSERT INTO cargo_products (sale_id, cargo_id, company_id, tracking_number, request, `status`) VALUES ($sale_id, $cargoId, ".$this->companyId.", $tracking_number, '$request', 1)");
					}
					$this->Session->setFlash('Başarılı, '.$result, 'flash_success');
				}else{
					$this->Session->setFlash('Hata!, '.$result, 'flash_error');
				}
				$this->redirect('/sale/detail/'.$sale_id);
			}
			// Kargom nerede ?
			if($where && $cargoId && !$check){
				$this->autoRender = false;
				if($cargoId==5){
					$result = $cargo->KargoBilgileriByReferans($sale_id);
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
				if($cargoId==10){
					if($contract=="self"){
						$result = $cargo->queryShipment($sale_id);
						if($result['res']->ShippingDeliveryVO->outFlag==0){
							echo $result['res']->ShippingDeliveryVO->shippingDeliveryDetailVO->operationMessage;
						}else{
							echo $result['res']->ShippingDeliveryVO->outResult;
						}
					}else{
						$result = $cargo->listInvDocumentInterfaceByReference([],["fieldName"=>3, "fieldValueArray"=>$sale_id]);
						if($result['res']->ShippingDataResponseVO->outFlag==0){
							echo $result['res']->ShippingDataResponseVO->shippingDataDetailVOArray->transactionMessage;
						}else{
							echo $result['res']->ShippingDataResponseVO;
						}
					}
				}
				exit;
			}
			// Kargo iptali
			if($cancel && $cargoId && !$check){
				$this->autoRender = false;
				$cpID = $data['cpID'];
				$sale_id = $data['sale_id'];
				$iptal = false;
				if($cargoId==5){
					$result = $cargo->MusteriSiparisIptal($sale_id);
					if($result['res']->MusteriTeslimatIptalIstegiResult){
						$iptal = true;
						// Kuyruğa eklenen datanın siliniyor
						$queueId = $this->Queue->find('first',[
							'conditions' => [
								"company_id"=>$this->companyId,
								"queue"=>"mng",
								"payload LIKE" => "%".$sale_id."%",
								"callable_method" => "cargo"
							]
						]);
						if(!empty($queueId) && $queueId['Queue']['id']){
							$this->Queue->delete($queueId['Queue']['id']);
						}
						echo json_encode($result['res']->pYapilanIslem);
					}else{
						echo json_encode($result['res']->pWsError);
					}
				}
				if($cargoId==10){
					if($contract=="self"){
						$result = $cargo->cancelShipment($sale_id);
						if($result['res']->ShippingOrderResultVO->outFlag==0){
							$iptal = true;
							echo "Kargo iptal işlemi gerçekleştirildi";
						}else{
							echo $result['res']->ShippingOrderResultVO->outResult;
						}
					}else{
						$req["ngiCargoKey"] = $sale_id;
						$req["ngiDocumentKey"] = $sale_id;
						$req["cancellationDescription"] = "Kobisi.com";
						$result = $cargo->cancelNgiShipment($req);
						if($result['res']->XCancelShipmentResponse->outFlag==0){
							$iptal = true;
							echo "Kargo iptal işlemi gerçekleştirildi";
						}else{
							echo $result['res']->XCancelShipmentResponse->outResult;
						}
					}
				}
				if($iptal){
					// kobisi entegrasyonu varsa ve iptal ediliyorsa alınan kargo bedeli iade edilmeli
					$this->db->query("UPDATE sales SET `status`=$status, tracking_number='' WHERE company_id=".$this->companyId." AND id=".$sale_id);
					$this->db->query("UPDATE cargo_products SET `status`=0 WHERE company_id=".$this->companyId." AND sale_id=".$sale_id);
				}
				exit;
			}
			// Kargo Şablon
			if($sablon && $cargoId){
				// Adres bilgileri
				$address = $this->db->query("SELECT ca.firstname, ca.lastname, ca.city, ca.district, ca.address, ca.phone, cs.fullname, cs.address, cs.city_name, cs.district_name, cs.phone 
				FROM customer_addresses ca 
				INNER JOIN cargo_senders cs ON cs.company_id=$this->companyId 
				WHERE ca.id=(SELECT shipping_address_id FROM sales s WHERE s.id=$sale_id);");
				$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->company['name']), '-'));
				$address[0]['logo'] = base64_encode(file_get_contents("https://cdn.kobisi.com/cdn/logo/".$this->companyId."/".$slug));
				$address[0]['barkod'] = "";
				// Barkod oluşturma MNG barkod işlemleri için EPL/ZPL formatına göre işlem yapılacak
				// Gönderi no için cron yazılarak KargoBilgileriByReferans sorgu sonucu ile gönderi takip no alınacak master
				if($cargoId==5){
					$request = $this->db->query("SELECT request FROM cargo_products cp 
					WHERE company_id=".$this->companyId." AND sale_id=".$sale_id);
					$request = json_decode($request[0]['cp']['request']);
					$parcaList = explode(':',$request->pKargoParcaList);
					$GonderiParca = [
						"GonderiParca" => ['Adet'=>1, 'Desi'=>$parcaList[1], 'Kg'=>$parcaList[0], 'Icerik'=>$parcaList[3]]
					];
					$barkod = $cargo->MNGGonderiBarkod($sale_id,$GonderiParca);
					if($barkod['res']->MNGGonderiBarkodResult->IstekBasarili){
						$address[0]['barkod'] = $this->zpl($barkod['res']->MNGGonderiBarkodResult->GonderiBarkods->GonderiBarkodBilgi->BarkodText);
					}
				}
				echo json_encode(reset($address));
				exit;
			}
		}
		// kargo entegrasyonu var mı ?
		$cargos = $this->db->query("SELECT c.* FROM company_cargo cc LEFT JOIN cargos c ON c.id=cc.cargo_id WHERE cc.is_active=1 AND cc.company_id=".$this->companyId);
		$cargo_products = $this->db->query("SELECT cp.id, cp.sale_id, cp.tracking_number, c.id, c.name, c.slug FROM cargo_products cp LEFT JOIN cargos c ON c.id=cp.cargo_id WHERE cp.sale_id=$sale_id AND cp.status=1 AND cp.company_id=".$this->companyId);
		$this->set('cargos', $cargos);
		$this->set('cargo_products', $cargo_products);
	}

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
