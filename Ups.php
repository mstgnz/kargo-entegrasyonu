<?php

class Ups{

    private $username;
    private $password;
    private $customerNumber;
    private $sessionID;
    private $urlMode;
    private $soap;

    private $urls = [
        "test" => "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?WSDL",
        "live" => "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?WSDL"
    ];

    private $neccessary = [
        "CreateShipment_Type2" => [
            "ShipperName", "ShipperAddress", "ShipperCityCode", "ShipperAreaCode", "ShipperMobilePhoneNumber", "ShipperEMail", "ConsigneeName","ConsigneeAddress", "ConsigneeCityCode", "ConsigneeAreaCode", "ConsigneeMobilePhoneNumber", "ConsigneeEMail", "PaymentType", "SmsToConsignee", "ShipperAccountNumber"
        ]
    ];

    private $unnecessary = [
        "CreateShipment_Type2" => [
            "ShipperContactName"=>"", "ShipperPostalCode"=>"", "ShipperPhoneExtension"=>"", "ShipperPhoneNumber"=>"", "ShipperExpenseCode"=>"", "ConsigneeAccountNumber"=>"", "ConsigneeContactName"=>"", "ConsigneeExpenseCode"=>"", "ConsigneePostalCode"=>"", "ConsigneePhoneNumber"=>"", "CustomerReferance"=>"", "CustomerInvoiceNumber"=>"", "DescriptionOfGoods"=>"", "DeliveryNotificationEmail"=>"", "NumberOfPackages"=>1, "PackageType"=>"K", "ServiceLevel"=>3, "IdControlFlag"=>0, "PhonePrealertFlag"=>0, "InsuranceValue"=>0, "InsuranceValueCurrency"=>"", "ValueOfGoods"=>0, "ValueOfGoodsCurrency"=>"", "ValueOfGoodsPaymentType"=>0, "ThirdPartyAccountNumber"=>"", "ThirdPartyExpenseCode"=>"", "SmsToShipper"=>0
        ]
    ];

    function __construct($username, $password, $customerNumber, $urlMode="test"){
        $this->username = $username;
        $this->password = $password;
        $this->customerNumber = $customerNumber;
        $this->urlMode = $urlMode;
        $this->soap = new \SoapClient($this->urls[$this->urlMode], array('trace' => true));
    }

    // Session
    public function Login_Type1(){
        $data = [
            "CustomerNumber" => $this->customerNumber,
            "UserName" => $this->username,
            "Password" => $this->password
        ];
        try{
            $res = $this->soap->Login_Type1($data);
            if(!empty($res->Login_Type1Result->SessionID)){
                $this->sessionID = $res->Login_Type1Result->SessionID;
                return $this->sessionID;
            }
            return false;
            //return ["status"=>"error", "res"=>$res->Login_Type1Result->ErrorDefinition];
        }catch (Exception $e){
            //echo "REQUEST:\n" . htmlentities($this->soap->__getLastRequest()) . "\n";exit;
            return false;
        }
    }

    // Session
    public function Login_V1(){
        $data = [
            "CustomerNumber" => $this->customerNumber,
            "UserName" => $this->username,
            "Password" => $this->password
        ];
        try{
            $res = $this->soap->Login_V1($data);
            if(!empty($res->Login_V1Result->SessionID)){
                $this->sessionID = $res->Login_V1Result->SessionID;
                return $this->sessionID;
            }
            return false;
            //return ["status"=>"error", "res"=>$res->Login_Type1Result->ErrorDefinition];
        }catch (Exception $e){
            //echo "REQUEST:\n" . htmlentities($this->soap->__getLastRequest()) . "\n";exit;
            return false;
        }
    }

    // Kargo Oluştur
    public function CreateShipment_Type2($args=[]){
        if($this->Login_Type1()){
            if(!$this->arrayCheck($args, "CreateShipment_Type2")){
                return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
            }
            $merge = array_merge($args, $this->unnecessary["CreateShipment_Type2"]);
            $data = [
                "SessionID" => $this->sessionID,
                "ShipmentInfo" => $merge,
                "ReturnLabelLink" => true,
                "ReturnLabelImage" => true
            ];
            try{ 
                $res = $this->soap->CreateShipment_Type2($data);
                return ["status"=>"success", "res"=>$res];
            }catch (Exception $e){
                return ["status"=>"error", "res"=>$e->getMessage()];
            }
        }else{
            return ["status"=>"error", "res"=>"Oturum açılamadı!"];
        }
    }

    // Kargo iptal
    public function Cancel_Shipment_V1($customerCode, $waybillNumber){
        if($this->Login_Type1()){
            $data = [
                "sessionId" => $this->sessionID,
                "customerCode" => $customerCode,
                "waybillNumber" => $waybillNumber
            ];
            try{ 
                $res = $this->soap->Cancel_Shipment_V1($data);
                return ["status"=>"success", "res"=>$res];
            }catch (Exception $e){
                return ["status"=>"error", "res"=>$e->getMessage()];
            }
        }else{
            return ["status"=>"error", "res"=>"Oturum açılamadı!"];
        }
    }

    // Kargom Nerede
    public function GetShipmentInfoByTrackingNumber_V1($trackingNumber){
        $url = "http://ws.ups.com.tr/QueryPackageInfo/wsQueryPackagesInfo.asmx?WSDL";
        $this->soap = new \SoapClient($url, array('trace' => true));
        if($this->Login_V1()){
            $data = [
                "SessionID" => $this->sessionID,
                "InformationLevel" => 1,
                "TrackingNumber" => $trackingNumber
            ];
            try{
                $res = $this->soap->GetShipmentInfoByTrackingNumber_V1($data);
                return $res->GetShipmentInfoByTrackingNumber_V1Result->PackageInformation[0];
            }catch (Exception $e){
                return ["status"=>"error", "res"=>$e->getMessage()];
            }
        }else{
            return ["status"=>"error", "res"=>"Oturum açılamadı!"];
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

    private function xmlOutput($array){
        $xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        $this->array_to_xml($array,$xml_data);
        header('Content-Type: application/xml; charset=utf-8');
        echo'<pre>';
        print_r($xml_data->asXML());
        exit;
    }

    private function array_to_xml( $data, &$xml_data ) {
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if( is_numeric($key) ){
                    $key = 'item'.$key;
                }
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
}
