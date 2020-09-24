<?php

class Mng{

    private $soap, $urlMode;
    private $data = [];
    private $urls = [
        "test" => "http://service.mngkargo.com.tr/tservis/musterikargosiparis.asmx?WSDL",
        "live" => "http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx?WSDL"
    ];

    private $necessary = [
        "SiparisGirisiDetayliV3" => [
            "pChIrsaliyeNo", "pPrKiymet", "pChBarkod", "pGonderiHizmetSekli", "pTeslimSekli", "pFlAlSms", "pFlGnSms", "pKargoParcaList", "pAliciMusteriAdi", "pChSiparisNo", "pLuOdemeSekli", "pFlAdresFarkli", "pChIl", "pChIlce", "pChAdres", "pChTelCep", "pChEmail", "pMalBedeliOdemeSekli","pFlKapidaOdeme"
        ]
    ];

    private $unnecessary = [
        "SiparisGirisiDetayliV3" => [
            "pChIcerik"=>"","pAliciMusteriMngNo"=>"","pAliciMusteriBayiNo"=>"","pChSemt"=>"","pChMahalle"=>"","pChMeydanBulvar"=>"","pChCadde"=>"","pChSokak"=>"","pChFax"=>"","pChVergiDairesi"=>"","pChVergiNumarasi"=>"","pPlatformKisaAdi"=>"","pPlatformSatisKodu"=>""
        ],
        "KargoBilgileriByReferans" => [
            "pGonderiNo"=>"","pFaturaSeri"=>"","pFaturaNo"=>"","pIrsaliyeNo"=>"","eFaturaNo"=>"","pRaporType"=>"GIDEN"
        ]
    ];

    function __construct($username=false, $password=false, $urlMode="test"){
        if($username && $password){
            $this->data["pKullaniciAdi"] = $username;
            $this->data["pSifre"] = $password;
            $this->data["WsUserName"] = $username;
            $this->data["WsPassword"] = $password;
            $this->data["pMusteriNo"] = $username;
            $this->urlMode = $urlMode;
            $this->soap = new \SoapClient($this->urls[$this->urlMode], array('trace' => true));
        }
    }

    // Kargo Oluştur
    public function SiparisGirisiDetayliV3($args=[]){
        if(!$this->arrayCheck($args, "SiparisGirisiDetayliV3")){
            return ["status"=>"error", "res"=>'Parametreleri eksiksiz giriniz!'];
        }
        $this->unnecessary["SiparisGirisiDetayliV3"]["pChTelEv"] = $args['pChTelCep'];
        $this->unnecessary["SiparisGirisiDetayliV3"]["pChTelIs"] = $args['pChTelCep'];
        $this->data = array_merge($this->data, $args, $this->unnecessary["SiparisGirisiDetayliV3"]);
        try{
            $res = $this->soap->SiparisGirisiDetayliV3($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Kargom Nerede
    public function KargoBilgileriByReferans($pSiparisNo=false){
        if(!$pSiparisNo){
            return ["status"=>"error", "res"=>'Sipariş numarası giriniz!'];
        }
        $this->data = array_merge($this->data, ["pSiparisNo"=>$pSiparisNo], $this->unnecessary['KargoBilgileriByReferans']);
        try{
            $res = $this->soap->KargoBilgileriByReferans($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Kargo iptal - ürün kargoya verilmediyse
    public function MusteriSiparisIptal($pMusteriSiparisNo=false){
        if(!$pMusteriSiparisNo){
            return ["status"=>"error", "res"=>'Sipariş numarası giriniz!'];
        }
        $this->data = array_merge($this->data, ["pMusteriSiparisNo"=>$pMusteriSiparisNo]);
        try{
            $res = $this->soap->MusteriTeslimatIptalIstegi($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }
    // Kargo iptal - ürün kargoya verildiyse
    public function MusteriTeslimatIptalIstegi($pSiparisNo=false, $pIslemAciklama=""){
        if(!$pSiparisNo){
            return ["status"=>"error", "res"=>'Sipariş numarası giriniz!'];
        }
        $this->data = array_merge($this->data, ["pSiparisNo"=>$pSiparisNo, "pIslemAciklama"=>$pIslemAciklama]);
        try{
            $res = $this->soap->MusteriTeslimatIptalIstegi($this->data);
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    // Barkod Oluştur
    public function MNGGonderiBarkod($pSiparisNo=false, $parcaList=[]){
        if(!$pSiparisNo || empty($parcaList)){
            return ["status"=>"error", "res"=>'Sipariş numarası giriniz!'];
        }
        $data = [
            "req" => [
                "pKullaniciAdi"=>$this->data["pKullaniciAdi"],
                "pSifre"=>$this->data["pSifre"],
                "WsUserName"=>$this->data["WsUserName"],
                "WsPassword"=>$this->data["WsPassword"],
                "pMusteriNo"=>$this->data["pMusteriNo"],
                "pChSiparisNo"=>$pSiparisNo,
                "pChIrsaliyeNo"=>$pSiparisNo,
                "ReferansNo"=>$pSiparisNo,
                "IrsaliyeNo"=>$pSiparisNo,
                "OutBarkodType"=>"ZPL",
                "pFlKapidaTahsilat"=>"0",
                "FlKapidaTahsilat"=>"0",
                "HatadaReferansBarkoduBas"=>1,
                "UrunBedeli"=>"",
                "ChMesaj"=>"",
                "EkString1"=>"",
                "EkString2"=>"",
                "EkString3"=>"",
                "EkString4"=>"",
                "ParcaBilgi"=>$parcaList
            ]
        ];
        //$this->xmlOutput($data);
        try{
            $res = $this->soap->MNGGonderiBarkod($data);
            //echo "REQUEST:\n" . htmlentities($this->soap->__getLastRequest()) . "\n";exit;
            return ["status"=>"success", "res"=>$res];
        }catch (Exception $e){
            return ["status"=>"error", "res"=>$e->getMessage()];
        }
    }

    private function arrayCheck($args, $key){
		$result = false;
		if(is_array($args)){
			foreach ($this->necessary[$key] as $value) {
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
