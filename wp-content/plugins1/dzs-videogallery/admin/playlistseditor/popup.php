<?php











function xss_clean($data, $pargs){


    $margs = array(
        'exclude_quot'=>false
    );


    if($pargs){
        $margs = array_merge($margs,$pargs);
    }


    if($margs['exclude_quot']){
        $data = str_replace("'",'&apos;',$data);
    }



    $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

// Remove any attribute starting with "on" or xmlns
    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

// Remove javascript: and vbscript: protocols
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

// Remove namespaced elements (we do not need them)
    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

    do
    {
        // Remove really unwanted tags
        $old_data = $data;
        $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
    }
    while ($old_data !== $data);

// we are done...
    return $data;
}

    $struct_tag = '<div class="admin-item-playlist">
                <div class="tag-title">Playlist <span class="delete-tag">delete this playlist</span></div>
                <div class="setting">
                <span class="label">Playlist ID</span><input type="text" value="" class="textinput" name="playlistid" placeholder="enter the playlist id here"/>
                </div>
            </div>';
    
    
        $aux = str_replace(array("\r", "\r\n", "\n"), '', $struct_tag);
        
        $initer = '';
        if(isset($_GET['initer'])){


            if(isset($_GET['initer'])){
                $initer = xss_clean($_GET['initer'], array(
                    'exclude_quot'=>true
                ));
            }
        }
        
?>
<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="popup.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="popup.js"></script>
    <script>
        <?php echo "var struct_tag = '" . $aux . "';"; ?>
        <?php echo "var initer = '" . $initer . "';"; ?>
    </script>
    </head>
    <body>
        <div class="add-tag">add a playlist</div>
        <div class="con-tags">
        </div>
        <hr>
        <div class="btn-submit">submit</div>
        <div class="sidenote">The YouTube API limit for videos retrieved is 50. To overcome this, you can add your 
            playlist multiple times. So if your playlist contains 100 videos, you would add the same playlist twice here.</div>
    </body>
</html>