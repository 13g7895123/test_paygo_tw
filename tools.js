export function copy (tagId)  {
// export const copy = (tagId) => {
    $('#inp_copy').css('display', 'block')
    $('#inp_copy').val($(`#${tagId}`).text())

    // alert($(`#${tagId}`).text())
    // console.log('test: ' + $(`#${tagId}`).text());

    var element = $('#inp_copy')
    element.select()
    document.execCommand('copy')
    $('#inp_copy').css('display', 'none')
    alert('已複製到剪貼簿!');
}