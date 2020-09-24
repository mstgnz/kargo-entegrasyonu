<?php if(in_array($sale['status'],[1,11,21,2,12,22,3,4,13,23]) && !empty($cargos)){ ?>
<div class="block">
    <div class="block-header block-header-default p-10">
        <h3 class="block-title">Kargo Entegrasyon</h3>
    </div>
    <div class="block-content p-10">
        <select name="cargo" class="form-control mb-1">
            <option value="">Seçiniz</option>
            <?php foreach ($cargos as $k => $v) {
                echo '<option value="'.$v['c']['slug'].'" data-id="'.$v['c']['id'].'">'.$v['c']['name'].'</option>';
            }?>
        </select>
        <div id="cargoForm"></div>
    </div>
</div>
<?php } ?>
<canvas id="mng"></canvas>
<script text="application/javascript">
    $(document).ready(function(){
        // cargo select
        $('select[name=cargo]').on('change',function(e){
            var cargoId = $(this).find(':selected').data('id');
            cargoId = cargoId ? cargoId : "";
            if(e.target.value=="mng"){
                $('#cargoForm').html(`<?php echo $this->element('Sale/cargo/mng'); ?>`);
                $('input[name="cargoID"]').val(cargoId);
            }else if(e.target.value=="yurtici"){
                $('#cargoForm').html(`<?php echo $this->element('Sale/cargo/yurtici'); ?>`);
                $('input[name="cargoID"]').val(cargoId);
                // kargo ağırlık
                $('input[name="docCargoDataArray[cargoWeight]"]').keyup(function(e){
                    $('input[name="shipmentData[totalWeight]"]').val(e.target.value)
                });
            }else{
                $('#cargoForm').html("");
            }
        });
    });
    // Mng kargo parça list
    mngParca = (e) => {
        var no, icerik, kilo, desi, kgdesi, length, context;
        $('#parcaModal').modal('show');
        $('#addParca').on('click', function(e){
            length = $('#parcaModal input[name="no[]"]').length;
            length = 1; // koli olacağı için kaç ürün olduğu önemli değil koli desi bilgisi kafi
            for (let i=0; i<length; i++) {
                no = $('#parcaModal input[name="no[]"]')[i].value
                icerik = $('#parcaModal input[name="icerik[]"]')[i].value
                kilo = $('#parcaModal input[name="kilo[]"]')[i].value
                desi = $('#parcaModal input[name="desi[]"]')[i].value
                kgdesi = $('#parcaModal input[name="kgdesi[]"]')[i].value
                context += `${kilo}:${desi}:${kgdesi}:${icerik}:${no}:;`;
            }
            $('#parcaModal').modal('hide');
            $('input[name="pKargoParcaList"]').val(context.split('undefined')[1]);
        });
    }
    // Mng kapıda ödeme kontrol
    odemeSekli = (e) => {
        if(e.target.value=="U"){
            $('#pFlKapidaOdeme').show();
        }else{
            $('#pFlKapidaOdeme').hide();
        }
    }
    kapidaOdeme = (e) => {
        if(e.target.value==1){
            $('#pMalBedeliOdemeSekli').show();
        }else{
            $('#pMalBedeliOdemeSekli').hide();
        }
    }
    // Mng Sipariş no barkod noy olarak ata
    siparisNo = (e) => {
        $('input[name=pChBarkod]').val(e.target.value);
    }
    // Mng form
    mngSubmit = (e) => {
        var parcaList = $('input[name=pKargoParcaList]').val();
        if(parcaList){
            $('#mngForm').submit();
        }else{
            Swal.fire({
                title : "Hata",
                icon : "error",
                text : "Parça listesi bilgileri boş bırakılamaz!"
            })
        }
    }
    // Yurtiçi form
    yurticiSubmit = (e) => {
        $('#yurticiForm').submit();
    }
    // Yurtiçi kargo tipi
    cargoType = (e) => {
        $('#cargoTypeVal').val(e.target.value);
    }
    // Yurtiçi kargo key
    ngiCargoKey = (e) => {
        $('#ngiCargoKeyVal').val(e.target.value);
    }
    // Kargom nerede
    cargoWhere = (e, cargo_id, sale_id, tracking_number) => {
        $('#cargo-where').append(` <i class="fa fa-spinner fa-spin"></i>`);
        $.ajax({
            type: "POST",
            url: '/sale/detail/'+sale_id,
            data: { 'type':'where', 'cargoID': cargo_id, 'tracking_number': tracking_number },
            success: function(result){
                $('#cargo-where i').remove();
                if(cargo_id==5){
                    mng = `<table class="table"><thead class="thead-dark"><tr><th scope="col">Başlık</th><th scope="col">Açıklama</th></tr></thead><tbody>`;
                    $.each(JSON.parse(result),function(k,v){
                        mng += `<tr><td style="text-align:left">${k}</td><td style="text-align:left">${v}</td></tr>`;
                    });
                    mng += '</tbody></table>';
                    Swal.fire({
                        title : "Kargom Nerede?",
                        icon : "info",
                        html : mng,
                        customClass: 'swal-wide',
                    })
                }else{
                    Swal.fire({
                        title : "Kargom Nerede?",
                        icon : "info",
                        text : result
                    })
                }
            }
        });
    }
    // Kargo iptal
    cargoCancel = (e, cp_id, cargo_id, sale_id, tracking_number, status) => {
        Swal.fire({
            title: 'Kargo İptal!',
            text: 'Kargo işlemini iptal etmek istediğinize emin misiniz?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d26a5c',
            confirmButtonText: 'İşlemi Onayla',
            cancelButtonText: 'İptal Et'
        }).then(function(result) {
            if (result.value) {
                $('#cargo-cancel').append(` <i class="fa fa-spinner fa-spin"></i>`);
                $.ajax({
                    type: "POST",
                    url: '/sale/detail/'+sale_id,
                    data: { 'type':'cancel', 'cpID':cp_id, 'cargoID':cargo_id, 'sale_id':sale_id, 'tracking_number':tracking_number, 'status':status },
                    success: function(result){
                        $('#cargo-cancel i').remove();
                        Swal.fire({
                            title : "Kargo İptal",
                            icon : "info",
                            text : result
                        }).then(()=>{
                            window.location = '/sale/detail/'+sale_id;
                        });
                    }
                });
            }
        });
    }
    // Şablon - Gönderici ve alıcı bilgileri
    cargoSablon = (e, cargo_id, sale_id) => {
        $('#cargo-sablon').append(` <i class="fa fa-spinner fa-spin"></i>`);
        $.ajax({
            type: "POST",
            url: '/sale/detail/'+sale_id,
            data: { 'type':'sablon', 'cargoID':cargo_id, 'sale_id':sale_id },
            success: function(response){
                $('#cargo-sablon i').remove();
                response = JSON.parse(response);
                sablon = `
                <div id="print-sablon" class="card" style="text-align:left">
                        <img src="data:image/png;base64,${response.logo}" alt="logo" style="margin-bottom:20px; width:100%" />
                        <div class="card-body">
                            <h5 class="card-title">Gönderici Bilgileri</h5>
                            <p class="card-text">${response.cs.fullname}<br/>${response.cs.phone}<br/>${response.cs.address} <br />${response.cs.city_name} / ${response.cs.district_name}</p>
                            <br/>
                            <h5 class="card-title">Alıcı Bilgileri</h5>
                            <p class="card-text">${response.ca.firstname} ${response.ca.lastname}<br/>${response.ca.phone}<br/>${response.ca.address} <br />${response.ca.city} / ${response.ca.district}</p>
                            <img id="barcode" src="${textToBase64Barcode('<?php echo $sale['id']; ?>')}" />
                </div></div>`;
                if(cargo_id==5){ // mng ise sadece ZPL formatı barkodu basılır
                    if(response.barkod!==""){
                        sablon = `<div id="print-sablon" class="card"><img src="data:image/png;base64,${response.barkod}" style="width:100%" /></div>`;
                    }else{
                        sablon = 'Yetki hatası, Lütfen MNG ile iletişime geçiniz.';
                    }
                } 
                Swal.fire({
                    showCancelButton: true,
                    confirmButtonColor: '#d26a5c',
                    confirmButtonText: 'Yazdır',
                    cancelButtonText: 'Çıkış',
                    html: sablon
                }).then((result)=>{
                    if (result.value) {
                        var divContents = document.getElementById("print-sablon").innerHTML;
                        var a = window.open('', '', 'height=500, width=400');
                        a.document.write('<html><body>');
                        a.document.write(divContents);
                        a.document.write('</body></html>');
                        a.document.close();
                        a.print();
                    }
                });
            }
        });
    }
    // Desi Hesapla modal
    desiAdd = (e) => {
        var width, height, length, result;
        $('#desiModal').modal('show');
        $('#addDesi').on('click', function(e){   
            width = $('#desiModal input[name="width"]').val()
            height = $('#desiModal input[name="height"]').val()
            length = $('#desiModal input[name="length"]').val()
            result = (width * height * length / 3000).toFixed(2);
            $('#desiModal').modal('hide');
            $('input[name="docCargoDataArray[cargoDesi]"]').val(result);
        });
    }
    // Barkode
    function textToBase64Barcode(text){
        var canvas = document.createElement("canvas");
        JsBarcode(canvas, text);
        return canvas.toDataURL("image/png");
    }
    // Barkod Rotation
    function rotateBase64Image(base64data, callback) {
        var canvas = document.getElementById("mng");
        var ctx = canvas.getContext("2d");
        var image = new Image();
        image.src = base64data;
        image.onload = function() {
            ctx.translate(image.width, image.height);
            ctx.rotate(90 * Math.PI / 90);
            ctx.drawImage(image, 0, 0); 
            window.eval(""+callback+"('"+canvas.toDataURL()+"')");
        };
    }
</script>

<style>
.swal-wide{
    display: table !important;
}
</style>
