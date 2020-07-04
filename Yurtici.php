<?php

class YurtIci{

    private $soap, $urlMode;
    private $data = [
        "wsUserName" => "YKTEST",
        "wsPassword" => "YK",
        "wsUserLanguage" => "TR",
        "payerCustData" => [
            "invCustId" => "111111111", // Yurtiçi tarafından verilen id
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
        ]
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
        "codData"=>[
            "ttInvoiceAmount"=>"","ttDocumentId"=>"","ttCollectionType"=>"","ttDocumentSaveType"=>"","dcSelectedCredit"=>"","dcCreditRule"=>""
        ],
        "EndofXSenderCustAddress" => [],
        "shipmentParamsData" => [
            "ngiDocumentKey"=>"", "specialFieldName"=>"", "specialFieldValue"=>""
        ],
        "custParamsVO" => [
            "invCustIdArray"=>"111111111", "senderCustIdArray"=>"", "receiverCustIdArray"=>""
        ],
        "EndOfCustParamsVO" => [
            "docIdArray"=>"", "startDate"=>"", "endDate"=>"", "dateParamType"=>"", "withCargoLifecycle"=>"1"
        ]
    ];

    function __construct($username=false, $password=false, $urlMode="test"){
        if($username && $password){
            $this->data["wsUserName"] = $username;
            $this->data["wsPassword"] = $password;
        }
        $this->urlMode = $urlMode;
        $this->soap = new \SoapClient($this->urls['shipment'][$this->urlMode], array('trace' => true));
    }

    public function createNgiShipmentWithAddress($shipmentData=[], $docCargoDataArray=[], $XSenderCustAddress=[], $XConsigneeCustAddress=[]){
        if(!$this->arrayCheck($shipmentData, "shipmentData") && !$this->arrayCheck($docCargoDataArray, "docCargoDataArray") && !$this->arrayCheck($XSenderCustAddress, "XSenderCustAddress") && !$this->arrayCheck($XConsigneeCustAddress, "XConsigneeCustAddress")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->data["shipmentData"] = array_merge($shipmentData, $this->unneccessary['shipmentData']);
        $this->data["shipmentData"]["docCargoDataArray"] = $docCargoDataArray;
        $this->data["shipmentData"]["specialFieldDataArray"] = $this->unneccessary['specialFieldDataArray'];
        $this->data["shipmentData"]["complementaryProductDataArray"] = $this->unneccessary['complementaryProductDataArray'];
        $this->data["shipmentData"]["codData"] = $this->unneccessary['codData'];
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
