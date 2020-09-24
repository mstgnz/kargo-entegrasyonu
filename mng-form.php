<?php $status = $sale['status'] % 10; ?>
<form method="post" id="mngForm">
    <input type="hidden" name="type" value="cargo" />
    <input type="hidden" name="cargoID" />
    <input type="hidden" name="status" value="<?php echo ($sale['status']%10)+30; ?>" />
    <input type="hidden" name="pFlAdresFarkli" value="0" />
    <input type="hidden" name="customer_id" value="<?php echo $sale['customer']['id']; ?>">
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Adı Soyadı</span>
        </div>
        <input type="text" class="form-control" name="pAliciMusteriAdi" value="<?php echo $sale['customer']['firstname']; ?> <?php echo $sale['customer']['lastname']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Sipariş Numarası</span>
        </div>
        <input type="text" class="form-control" name="pChSiparisNo" onkeyup="siparisNo(event)" value="<?php echo $sale['id']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">İrsaliye No</span>
        </div>
        <input type="text" class="form-control" name="pChIrsaliyeNo" value="<?php echo $sale['id']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Barkod No</span>
        </div>
        <input type="text" class="form-control" name="pChBarkod" readonly value="<?php echo $sale['id']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Ürün Fiyatı</span>
        </div>
        <input type="text" class="form-control" name="pPrKiymet" value="<?php echo number_format($sale['grand_total_Arr'],2,',','.'); ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Kapıda Ödeme</span>
        </div>
        <select class="form-control" name="pFlKapidaOdeme">
            <option value="0">Hayır</option>
            <option value="1" <?php echo $status==3 || $status==4 ? "selected" : null;?>>Evet</option>
        </select>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Ödemeyi Kim Yapacak</span>
        </div>
        <select class="form-control" name="pLuOdemeSekli">
            <option value="P">Gönderici Ödemeli</option>
            <option value="U" <?php echo $status==3 || $status==4 ? "selected" : null;?>>Alıcı Ödemeli</option>
        </select>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Gönderim Tipi</span>
        </div>
        <select class="form-control" name="pGonderiHizmetSekli">
            <option value="NORMAL">Normal</option>
            <option value="GUNICI">Gün içi</option>
            <option value="AKSAM_TESLIMAT">Akşam Teslimat</option>
            <option value="ONCELIKLI">Öncelikli</option>
        </select>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Teslimat Tipi</span>
        </div>
        <select class="form-control" name="pTeslimSekli">
            <option value="1">Adrese Teslim</option>
            <option value="2">Alıcısı Haberli</option>
            <option value="3">Telefon İhbarlı</option>
        </select>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı SMS (ücretli)</span>
        </div>
        <select class="form-control" name="pFlAlSms">
            <option value="0">Sms Atılmasın</option>
            <option value="1">Gönderi Varış Şubesine Ulaştığında</option>
            <option value="2">Gönderi Fatura Kesildiğinde</option>
            <option value="3">Varış Şubesi Ulaştığında ve Fatura Kesildiğinde</option>
        </select>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Gönderici SMS (ücretli)</span>
        </div>
        <select class="form-control" name="pFlGnSms">
            <option value="0">Sms Atılmasın</option>
            <option value="1">Sms Atılsın</option>
        </select>
    </div>
    <div class="input-group mb-1" id="pMalBedeliOdemeSekli" style="display:none">
        <div class="input-group-prepend">
            <span class="input-group-text">Kapıda Ödeme Tipi</span>
        </div>
        <select class="form-control" name="pMalBedeliOdemeSekli">
            <option value="NAKIT">Nakit</option>
            <option value="KREDI_KARTI">Kredi Kartı</option>
        </select>
    </div>
    <div class="input-group mb-1" style="position:relative">
        <div class="input-group-prepend">
            <span class="input-group-text">Desi Bilgileri</span>
        </div>
        <input type="text" class="form-control" name="pKargoParcaList" readonly />
        <span onclick="mngParca(event)" style="position:absolute; top:8px; right:10px; cursor:pointer;"><i class="fas fa-lg fa-edit"></i></span>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı İli</span>
        </div>
        <input type="text" class="form-control" name="pChIl" readonly value="<?php  echo $sale['shipping_address']['city']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı İlçesi</span>
        </div>
        <input type="text" class="form-control" name="pChIlce" readonly value="<?php echo $sale['shipping_address']['district']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Adresi</span>
        </div>
        <input type="text" class="form-control" name="pChAdres" readonly value="<?php echo $sale['billing_address']['address']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Telefon</span>
        </div>
        <input type="text" class="form-control" name="pChTelCep" readonly value="<?php echo $sale['billing_address']['phone']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Email</span>
        </div>
        <input type="text" class="form-control" name="pChEmail" readonly value="<?php echo $sale['customer']['email']; ?>" />
    </div>
    <button type="button" onclick="mngSubmit(event)" class="btn btn-primary form-control">Kargolama İşlemlerini Başlat</button>
</form>

<!-- Parça Listesi Modal -->
<div class="modal fade" id="parcaModal" tabindex="-1" role="dialog" aria-labelledby="parcaModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="parcaModalLabel">Desi Bilgileri <br/> 
        <small>Ürünlerin kolilenmiş halinin desi bilgisi girilmelidir.</small></h5>
      </div>
      <div class="modal-body">
        <table class="table table-sm">
            <thead>
                <th>İçerik</th>
                <th>Kilo</th>
                <th>Desi</th>
                <th>Kg Desi</th>
            </thead>
            <tbody>
                <?php for($i=1; $i<=1;/*count($sale['products']);*/ $i++){ ?>
                <tr>
                    <input type="hidden" class="form-control" name="no[]" readonly value="<?php echo $i; ?>" />
                    <td><input type="text" class="form-control" name="icerik[]" /></td>
                    <td><input type="text" class="form-control" name="kilo[]" /></td>
                    <td><input type="text" class="form-control" name="desi[]" /></td>
                    <td><input type="text" class="form-control" name="kgdesi[]" /></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-primary" id="addParca">Ekle</button>
      </div>
    </div>
  </div>
</div>

<style>
    .input-group-text{ width: 150px; font-size:12px }
</style>
