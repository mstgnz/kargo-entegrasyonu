<?php $status = $sale['status'] % 10; ?>
<form method="post" id="yurticiForm">
    <input type="hidden" name="type" value="cargo" />
    <input type="hidden" name="cargoID" />
    <input type="hidden" name="status" value="<?php echo ($sale['status']%10)+30; ?>" />
    <input type="hidden" name="customer_id" value="<?php echo $sale['customer']['id']; ?>">
    <input type="hidden" name="docCargoDataArray[weightUnit]" value="KGM" />
    <input type="hidden" name="docCargoDataArray[cargoCount]" value="1" />
    <input type="hidden" name="docCargoDataArray[dimensionsUnit]" value="" />
    <input type="hidden" name="docCargoDataArray[cargoType]" value="2" id="cargoTypeVal" />
    <input type="hidden" name="shipmentData[personGiver]" value="<?php echo $company['name']; ?>" />
    <input type="hidden" name="docCargoDataArray[ngiCargoKey]" value="<?php echo $sale['id']; ?>" id="ngiCargoKeyVal" />
    <input type="hidden" name="shipmentData[productCode]" value="STA" maxlength="3" />
    <input type="hidden" name="shipmentData[totalDesi]" value="" />
    <input type="hidden" name="shipmentData[totalWeight]" value="" />
    <input type="hidden" name="shipmentData[totalCargoCount]" value="1" />
    <input type="hidden" name="shipmentData[cargoType]" value="2" />
    <input type="hidden" name="docCargoDataArray[length]" value="" />
    <input type="hidden" name="docCargoDataArray[width]" value="" />
    <input type="hidden" name="docCargoDataArray[height]" value="" />
    <input type="hidden" name="codData[ttDocumentId]" value="<?php echo $sale['id'];?>" /> 
    <input type="hidden" name="codData[ttDocumentSaveType]" value="0" />
    <input type="hidden" name="codData[dcSelectedCredit]" value="1" />
    <input type="hidden" name="codData[dcCreditRule]" value="1" />

    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">İrsaliye Numarası</span>
        </div>
        <input type="text" class="form-control" name="shipmentData[ngiDocumentKey]" value="<?php echo $sale['id']; ?>" onkeyup="ngiCargoKey(event)" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Ürün Bedeli</span>
        </div>
        <input type="text" class="form-control" name="codData[ttInvoiceAmount]" value="<?php echo number_format($sale['grand_total_Arr'],2); ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Ödeme Tipi</span>
        </div>
        <select class="form-control" name="codData[ttCollectionType]" readonly>
            <option value="">Gönderici Ödemeli</option>
            <option value="0" <?php echo $status==3 ? "selected" : null ;?>>Kapıda Nakit Ödeme</option>
            <option value="1" <?php echo $status==4 ? "selected" : null ;?>>Kapıda Kredi Kartı İle Ödeme</option>
        </select>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Kargo Desi</span>
        </div>
        <input type="number" class="form-control" name="docCargoDataArray[cargoDesi]" readonly />
        <span onclick="desiAdd(event)" style="position:absolute; top:8px; right:10px; cursor:pointer;"><i class="fas fa-lg fa-edit"></i></span>
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Kargo Ağırlık (kg)</span>
        </div>
        <input type="number" min="0" step="0.01" class="form-control" name="docCargoDataArray[cargoWeight]" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Adı Soyadı</span>
        </div>
        <input type="text" class="form-control" name="XConsigneeCustAddress[consigneeCustName]" value="<?php echo $sale['customer']['firstname']; ?> <?php echo $sale['customer']['lastname']; ?>"  />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Adresi</span>
        </div>
        <input type="text" class="form-control" name="XConsigneeCustAddress[consigneeAddress]" readonly value="<?php echo $sale['billing_address']['address']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı İl Kodu</span>
        </div>
        <input type="text" class="form-control" name="XConsigneeCustAddress[cityId]" readonly value="<?php  echo $sale['shipping_address']['city_id']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı İlçesi</span>
        </div>
        <input type="text" class="form-control" name="XConsigneeCustAddress[townName]" readonly value="<?php echo $sale['shipping_address']['district']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Telefon</span>
        </div>
        <input type="text" class="form-control" name="XConsigneeCustAddress[consigneeMobilePhone]" readonly value="<?php echo $sale['billing_address']['phone']; ?>" />
    </div>
    <div class="input-group mb-1">
        <div class="input-group-prepend">
            <span class="input-group-text">Alıcı Email</span>
        </div>
        <input type="text" class="form-control" name="XConsigneeCustAddress[consigneeEmailAddress]" readonly value="<?php echo $sale['customer']['email']; ?>" />
    </div>

    <button type="button" onclick="yurticiSubmit(event)" class="btn btn-primary form-control">Kargolama İşlemlerini Başlat</button>
</form>

<!-- Parça Listesi Modal -->
<div class="modal fade" id="desiModal" tabindex="-1" role="dialog" aria-labelledby="desiModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="desiModalLabel">Desi Bilgileri <br/> 
        <small>Ürünlerin kolilenmiş halinin desi bilgisi girilmelidir.</small></h5>
      </div>
      <div class="modal-body">
        <table class="table table-sm">
            <thead>
                <th>Genişlik</th>
                <th>Yükseklik</th>
                <th>Uzunluk</th>
            </thead>
            <tbody>
                <tr>
                    <td><input type="number" class="form-control" name="width" /></td>
                    <td><input type="number" class="form-control" name="height" /></td>
                    <td><input type="number" class="form-control" name="length" /></td>
                </tr>
            </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-primary" id="addDesi">Ekle</button>
      </div>
    </div>
  </div>
</div>

<style>
    .input-group-text{ width: 150px; font-size:12px }
</style>
