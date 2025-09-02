<?php 
/**
 * ????
 * @author loach
 * @copyright www.zhangbailong.com
 */
class Captcha{
 
private $img; //?��?���y�`
private $width=150; //?��?��
private $height=40; //?������
private $code;  //???
private $codeLength=4; //???��?��,4��
private $font; //�r�^ �r�^����b?�e?�����font��?�U
private $fontSize=22; //�r�^�j�p
private $fontColor;  //�r�^?��
private $randChar='0123456789'; //?��]�l,�h���F�@�Ǭۦ����r�ŨҦpo0
private $interfereChar='*-+.'; //�z��]�l
 
/**
 * �۳y��k�A��l�Ʀr�^
 */
public function __construct()
{
//?�K�bc?windows/font��?���@�ڦn�ݪ��^��r�^
$this->font=$_SERVER['DOCUMENT_ROOT'].'/assets/fonts/VERDANA.TTF';
}
 
/**
 * ?�ؤ@?�x�Ϊ�?��
 */
private function createImage()
{
//?��?��?���y�`
$this->img=imagecreatetruecolor($this->width,$this->height);
//?��?��?��,?��ȡ]0-255�^,�V�j?��V?�A�V�p?��V�`
$color=imagecolorallocate($this->img,mt_rand(180,255),mt_rand(180, 255), mt_rand(180, 255));
//�b?���W?�@?�x�Φ}��?���R
imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);
}
 
/**
 * �q??��]�l�ͦ�4��?��???
 */
private function createCode()
{
//?��X?��]�l��?�סA?�@�O?�F��K�Z��??�Φ���?��r��
$len=strlen($this->randChar)-1;
//�`??��???
for($i=0;$i<$this->codeLength;$i++)
{
$this->code.=$this->randChar[mt_rand(0,$len)];
}
}
 
/**
 * �V?������R��r
 */
private function fillFont()
{
$x=$this->width/$this->codeLength;
$y=$this->height/1.4;
//�`?�ͦ���r
for ($i=0;$i<$this->codeLength;$i++)
{
//?��?��r�^?��A�]??�O???��r�A�ҥH?��ȭn��?���I���n�`.
$this->fontColor=imagecolorallocate($this->img,mt_rand(0,150),mt_rand(0,150),mt_rand(0,150));
//�Φr�^�Q?��?�J�奻
imagettftext($this->img,$this->fontSize,mt_rand(-30,30),$x*$i+mt_rand(2, 5),$y,$this->fontColor,$this->font,$this->code[$i]);
}
}
 
/**
 * �V?������R�@�Ǥz��?
 */

private function fillLine()
{
//�ͦ�?��������z��?
for($i=0;$i<2;$i++)
{
//???��
$lineColor=imagecolorallocate($this->img,mt_rand(0,150),mt_rand(0,150),mt_rand(0,150));
//��R��?����
imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$lineColor);
}
}
 
/**
 * �z��]�l
 */
private function fillInterfereChar()
{
//�ͦ�100�z��]�l��R��?����
for($i=0;$i<20;$i++)
{
//?��
$color=imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
//?�z��]�l��R
imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),$this->interfereChar[mt_rand(0,3)],$color);
}
}
 
/**
 * ??��?�X?��
 */
private function outputImage()
{
//���w??��?�X?��??��
header('Content-type:image/png');
//?���榡?png
    imagepng($this->img);
    //?���O�@��?��?���A�̦Z�n?��
    imagedestroy($this->img);
}
 
/**
 * ?��???,�Τ_�e�x?��
 */
public function showCaptcha()
{
//?��?���I��
$this->createImage();
//��?4��???
$this->createCode();
//��R�z��?
//$this->fillLine();
//��R�z��]�l
$this->fillInterfereChar();
//????�H�Y���r�^��R
$this->fillFont();
//??��?�X???
$this->outputImage();
}
 
/**
 * ?��???�A�Τ_�Z�x??
 */
public function getCode()
{
return $this->code;
}
 
}
  
?>