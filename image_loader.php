<?php
/* Bertrand Belguise image_loader class
   this code is under Creative Common by-nc licence
   you can distribute this code and mofify it since
   you cite the original author */

/* usage
   $picture=new image_loader("path");
   $picture.thumbsize=100; in pixels default to 0 orginal size
   $picture.watermarkText="copyright B.belguise 2008";
   $picture.show();
*/
class image_loader
{
   public $thumbsize=0;
   public $watermarkText='';
   public $watermarkFont=1;
   public $watermarkColor='#FFFFFF';
   public $watermarkShadow=true;
   public $watermarkPos="br"; //bottom right first letters of (top,middle,bottom)(left,center,right)
   public $watermarkMargin=2;
   public $watermarkFile='';
   public $watermarkOpacity=60;
   public $quality=75;
   public $rotate=0;
   protected $picture,$pictureWidth,$pictureHeight,$pictureType,$pictureMimeType,$image,$watermark,$watermarkW,$watermarkH,$watermarkX,$watermarkY,$directory,$pictureName;

   function __construct($image_file)
   {
     if(! file_exists($image_file)) $this->error("no such photo : $image_file");
     $pictureInfo=getimagesize($image_file);
     if($pictureInfo):
        $this->pictureWidth = $pictureInfo[0]; 
        $this->pictureHeight = $pictureInfo[1]; 
        $this->pictureType = $pictureInfo[2]; 
        $this->pictureMimeType = $pictureInfo['mime'];
        if ($this->pictureType == IMAGETYPE_JPEG) :
           $this->picture = imagecreatefromjpeg($image_file); 
        elseif ($this->pictureType == IMAGETYPE_PNG):
           $this->picture = imagecreatefrompng($image_file);
        endif;
        $this->directory=dirname($image_file);
        $this->pictureName=basename($image_file);
     else :
        $this->error("$image_file corrupted");
     endif;
   }

   function __destruct()
   {
      imagedestroy($this->picture);
      imagedestroy($this->watermark);
      imagedestroy($this->image);
   }

   public function show()
   {
     header("Content-Type: " . $this->pictureMimeType);
     if($this->thumbsize>0){
       if ($this->pictureWidth>$this->pictureHeight)
       {
         $thumbWidth=$this->thumbsize;
         $thumbHeight=$this->thumbsize*$this->pictureHeight/$this->pictureWidth;
       } else {
         $thumbHeight= $this->thumbzise;
         $thumbWidth=$this->thumbsize*$this->pictureWidth/$this->pictureHeight;
       }
       $this->image=ImageCreateTrueColor($thumbWidth,$thumbHeight);
       imagecopyresampled($this->image,$this->picture,0,0,0,0,$thumbWidth,$thumbHeight,$this->pictureWidth,$this->pictureHeight);
     } else {
       $this->image =$this->picture;
       $thumbWidth=$this->pictureWidth;
       $thumbHeight=$this->pictureHeight;
     }
     $this->makeWatermark($thumbWidth,$thumbHeight);
     if($this->watermark)
     {
       $this->setWatermarkPos($thumbWidth,$thumbHeight);
       imagecopymerge($this->image,$this->watermark,$this->watermarkX,$this->watermarkY,0,0,$this->watermarkW,$this->watermarkH,$this->watermarkOpacity);
     }
     if ($this->pictureType == IMAGETYPE_JPEG) :
       imageJPEG($this->image,null,$this->quality); 
     elseif ($this->pictureType == IMAGETYPE_PNG):
       imagePNG($this->image);
     endif;
   }
   
   
   public function makeWatermark($maxW,$maxH)
   {
     $maxW=$maxW-2*$this->watermarkMargin;
     $maxH=$maxH-2*$this->watermarkMargin;
     $this->watermark=false;
     if($this->watermarkFile!=''):
        if(! file_exists($this->watermarkFile)) $this->error("watermark not found");
        $watermark_image_info = getimagesize($this->watermarkFile); 
        if ($watermark_image_info) :
            $watermark_image_width = $watermark_image_info[0]; 
            $watermark_image_height = $watermark_image_info[1]; 
            $watermark_image_imagetype = $watermark_image_info[2]; 
            $watermark_image_mime_type = $watermark_image_info['mime']; 
            if ($watermark_image_imagetype == IMAGETYPE_JPEG) { 
               $watermark_image = imagecreatefromjpeg($this->watermarkFile); 
            }  
            elseif ($watermark_image_imagetype == IMAGETYPE_PNG) { 
               $watermark_image = imagecreatefrompng($this->watermarkFile); 
            }
        else
          $this->error("Watermark file corrupted");
        endif;
     elseif ($this->watermarkText!=''):
        $watermark_image=$this->image_text($this->watermarkText);
        $watermark_image_height=imagefontheight($this->watermarkFont);
        $watermark_image_width=strlen($this->watermarkText)*imagefontwidth($this->watermarkFont);
        if($this->watermarkShadow) {$watermark_image_height++;$watermark_image_width++;}
     endif;
     if(isset($watermark_image))
     {
       $this->watermarkW=$watermark_image_width;
       $this->watermarkH=$watermark_image_height;
       if($watermark_image_width>$maxW or $watermark_image_height>$maxH)
       {
         $height= ($watermark_image_height>$maxH) ? $maxH : $watermark_image_height;
         $width= ($watermark_image_width>$maxW) ? $maxW : $watermark_image_width;
         $this->watermark=imageCreateTrueColor($width,$height);
         $bgcolor=imagecolorallocatealpha($this->watermark,0,0,0,127);
         imagecolortransparent($this->watermark,$bgcolor);
         imagefill($this->watermark,0,0,$bgcolor);
         $this->watermarkW=$width;
         $this->watermarkH=$height;
         imagecopyresampled($this->watermark,$watermark_image,0,0,0,0,$width,$height,$watermark_image_width,$watermark_image_height);
       } else $this->watermark=$watermark_image;
     }
    }

    private function setWatermarkPos($imageW,$imageH)
    {
      $hpos=substr($this->watermarkPos,1,1);
      $vpos=substr($this->watermarkPos,0,1);
      if($hpos=='l') $this->watermarkX=$this->watermarkMargin;
      elseif ($hpos=='c') $this->watermarkX=($imageW-$this->watermarkW)/2;
      else $this->watermarkX = $imageW - $this->watermarkW - $this->watermarkMargin;
      if($vpos=='t') $this->watermarkY=$this-watermarkMargin;
      elseif ($vpos=='m') $this->watermarkY=($imageH-$this->watermarkH)/2;
      else $this->watermarkY=$imageH-$this->watermarkH-$this->watermarkMargin;
    }

    private function image_text($text)
    {
      $color=$this->watermarkColor; 
      $red = hexdec(substr($color, 1, 2)); 
      $green = hexdec(substr($color, 3, 2)); 
      $blue = hexdec(substr($color, 5, 2));
      $text_height=imagefontheight($this->watermarkFont);
      $text_width=strlen($text)*imagefontwidth($this->watermarkFont);
      if($this->watermarkShadow) {$text_height++;$text_width++;}
      $text_image=imageCreateTrueColor($text_width,$text_height);
      $text_color = imagecolorallocate($text_image, $red, $green, $blue);  
      $shadow_color = imagecolorallocate($text_image, 20, 20, 20);
      $bgcolor=imagecolorallocatealpha($text_image,0,0,0,127);
      imagefill($text_image,0,0,$bgcolor);
      imagecolortransparent($text_image,$bgcolor);
      if ($this->watermarkShadow)  
        imagestring( $text_image,$this->watermarkFont,1,1,$text,$shadow_color ); 
      imagestring( $text_image,$this->watermarkFont,0,0,$text,$text_color ); 
      return $text_image;
    }

    private function error($text){
      header("Content-Type:image/png");
      imagePNG($this->image_text("error $text"));
      exit("error $text");
    }

}

$photo=new image_loader('./test.jpg');
$photo->thumbsize=100;
$photo->watermarkFont=1;
$photo->watermarkText='';
$photo->watermarkFile="CC.png";
$photo->watermarkPos="mc";
$photo->show();
//$photo->makeWatermark(500,500);
  //    header("Content-Type:image/png");
    //  imagePNG($photo->watermark);

