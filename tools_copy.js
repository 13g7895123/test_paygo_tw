$(document).ready(function(){
    $('.img_copy').click(() => {copy()})	
})

const copy = () => {
    navigator.clipboard.writeText($('.payment_code').attr('value'))
    .then(() => {
        Swal.fire({
            icon: 'success',
            title: '複製到剪貼簿!',
            showConfirmButton: true,
        })
    })
}