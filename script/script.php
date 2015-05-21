<?php

// R,G,B = 0-255 range
// A = 0.0 to 1.0 range

function colorize($file, $targetR, $targetG, $targetB, $targetA, $targetName ) {

    if (!extension_loaded('gd') && !extension_loaded('gd2')) {
        trigger_error("GD is not loaded", E_USER_WARNING);
        return false;
    }

    $im_src = imagecreatefrompng($file);

    $width = imagesx($im_src);
    $height = imagesy($im_src);

    $im_dst = imagecreatefrompng($file);

    // Turn off alpha blending and set alpha flag
    imagealphablending($im_dst, false);
    imagesavealpha($im_dst, true);

    // Fill transparent first (otherwise would result in black background)
    imagefill($im_dst, 0, 0, imagecolorallocatealpha($im_dst, 0, 0, 0, 127));

    for ($x=0; $x<$width; $x++) {
        for ($y=0; $y<$height; $y++) {
            $alpha = (imagecolorat( $im_src, $x, $y ) >> 24 & 0xFF);

            $col = imagecolorallocatealpha( $im_dst,
                $targetR - (int) ( 1.0 / 255.0 * (double) $targetR ),
                $targetG - (int) ( 1.0 / 255.0 * (double) $targetG ),
                $targetB - (int) ( 1.0 / 255.0 * (double) $targetB ),
                (($alpha - 127) * $targetA) + 127
            );

            if (false === $col) {
                die( 'sorry, out of colors...' );
            }

            imagesetpixel( $im_dst, $x, $y, $col );
        }
    }

    imagepng( $im_dst, $targetName);
    imagedestroy($im_dst);
}

function resize( $filename, $new_width, $new_height ){
    if (!extension_loaded('gd') && !extension_loaded('gd2')) {
        trigger_error("GD is not loaded", E_USER_WARNING);
        return false;
    }
    list($width, $height) = getimagesize($filename);
    $im_src = imagecreatefrompng($filename);
    $im_dst = imagecreatetruecolor($new_width, $new_height);
    
    imagealphablending($im_dst, false);
    imagesavealpha($im_dst, true);

    imagefill($im_dst, 0, 0, imagecolorallocatealpha($im_dst, 0, 0, 0, 127));

    imagecopyresampled($im_dst, $im_src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagepng( $im_dst, $filename);
    imagedestroy($im_dst);
}


function reconvert( $path = '.', $output = '.', $structure = array(), $level = 0 ){ 
    $ignore = array( 'cgi-bin', '.', '..', '.DS_Store', '.git' ); 
    $dh = @opendir( $path ); 
    //echo $path."\n";
    while( false !== ( $file = readdir( $dh ) ) ){ 
        if( !in_array( $file, $ignore ) ){ 
            $spaces = str_repeat( ' ', ( $level * 4 ) );  
            if( is_dir( "$path/$file" ) ){ 
                echo "$spaces $file/\n"; 
                if( !is_dir( "$output/$file" ) ){
                    mkdir( "$output/$file" );
                }
                reconvert( "$path/$file", "$output/$file" , $structure, ($level+1) ); 
            } else { 
                $ext = pathinfo("$path/$file", PATHINFO_EXTENSION);
                echo "$spaces $file ... OK\n";
                if( $ext == "png" ){
                    foreach ($structure as $folder => $color) {
                        if( !is_dir( "$output/$folder" ) ){
                            mkdir( "$output/$folder" );
                        }
                        colorize( "$path/$file", $color["colors"]["R"], $color["colors"]["G"], $color["colors"]["B"], $color["colors"]["A"], "$output/$folder/$file" );
                        if( isset( $structure[ $folder ]["size"] ) ){
                            resize( "$output/$folder/$file", $structure[ $folder ]["size"], $structure[ $folder ]["size"] );
                        }
                    }
                }
            } 
        } 
    } 
    closedir( $dh ); 
    // Close the directory handle 
} 

/* Folder structure and data to convert */
$png_structure = array(
    "tool" => array(
        "colors" => array("R" => 0xFF,"G" => 0xFF,"B" => 0xFF,"A" => 1)
    ) ,
    "toolactive" => array(
        "colors" => array("R" => 0x2A,"G" => 0x97,"B" => 0xCC,"A" => 1)
    ) ,
    "sitenav" => array(
        "colors" => array("R" => 0x78,"G" => 0x57,"B" => 0x3C,"A" => 1),
        "size"   => 16
    )
);

/* Treatment itself */
$input_dir  = "../png";
$output_dir = "../sakai_skin";

reconvert( $input_dir, $output_dir, $png_structure ); 

?>