<?php
/*
Plugin Name: Snap My Roll
Function file
*/

function imagecompare($img1, $img2) {
  if (imagesx($img1)!=imagesx($img2) || imagesy($img1)!=imagesy($img2)){
    return false;
  }
  for( $x=0; $x<imagesx($img1 ); $x++ ){
     for ($y=0; $y<imagesy($img1); $y++) {
          if(imagecolorat($img1,$x,$y)!=imagecolorat($img2,$x,$y)){
                  return false;
          }
     }
  }
  return true;
} 


function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
	curl_setopt($c, CURLOPT_REFERER, "http://www.websnapr.com/index.php");
	curl_setopt($c, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13');
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
            else return FALSE;
    }

function sanitize_filename($filename, $forceextension="")
{
/*
1. Remove leading and trailing dots
2. Remove dodgy characters from filename, including spaces and dots except last.
3. Force extension if specified
*/

$defaultfilename = "none";
$dodgychars = "[^0-9a-zA-Z()_-]"; // allow only alphanumeric, underscore, parentheses and hyphen

$filename = preg_replace("/^[.]*/","",$filename); // lose any leading dots
$filename = preg_replace("/[.]*$/","",$filename); // lose any trailing dots
$filename = $filename?$filename:$defaultfilename; // if filename is blank, provide default

$lastdotpos=strrpos($filename, "."); // save last dot position
$filename = preg_replace("/$dodgychars/","_",$filename); // replace dodgy characters
$afterdot = "";
if ($lastdotpos !== false) { // Split into name and extension, if any.
$beforedot = substr($filename, 0, $lastdotpos);
if ($lastdotpos < (strlen($filename) - 1))
$afterdot = substr($filename, $lastdotpos + 1);
}
else // no extension
$beforedot = $filename;

if ($forceextension)
$filename = $beforedot . "." . $forceextension;
elseif ($afterdot)
$filename = $beforedot . "." . $afterdot;
else
$filename = $beforedot;

return $filename;
}
?>
