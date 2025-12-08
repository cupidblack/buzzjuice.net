<?php
// some total cache vars that needs to be like this

function dzsvg_shortcode_player_builder(){
    global $dzsvg;


    $sample_data_installed = false;


    $ids = '';





    if(isset($dzsvg->sample_data) && isset($dzsvg->sample_data['media'])  ){

        for($i=0;$i<count($dzsvg->sample_data['media']);$i++){

            if($i>0){
                $ids.=',';
            }


        }
    }


    ?>

    <style>.setting #wp-content-editor-tools{ padding-top: 0; } body .sidenote{ color: #777777; }</style>
    <script>
        <?php
        if(isset($_GET['sel'])){
        $aux = str_replace(array("\r","\r\n","\n"),'',$_GET['sel']);
        $aux = str_replace("'",'"',$aux);
        echo 'window.dzsvg_startinit = \''.stripslashes($aux).'\';';



        }
        ?>


    </script>
    <style>
        #dzsvg-shortcode-tabs .tab-menu-con.is-always-active .tab-menu{
            padding-left: 15px;

        }
        #dzsvg-shortcode-tabs .tab-menu-con.is-always-active .tab-menu:before{
            display: none;;
        }
    </style>
    <div class="wrap wrap-for-generator-player <?php
    if($sample_data_installed){
        echo 'sample-data-installed';
    }
    ?>">
        <h3><?php echo esc_html__(" Shortcode Generator"); ?></h3>








    </div><?php






    $options_array = array();
    $ilab = 0;

    foreach($dzsvg->options_array_player as $lab => $opt){


        $opt = (array) $opt;

        if(is_numeric($lab) && isset($opt['name'])){
            $lab = $opt['name'];
        }

        $options_array[$ilab] = array(
            'type'=>$opt['type'],
            'param_name'=>$lab,
            'heading' => $opt['title'],
        );

        if(isset($opt['type'])){
            $options_array[$ilab]['type'] = $opt['type'];
            if($opt['type']=='select'){
                $options_array[$ilab]['type'] = 'dropdown';
            }
            if($opt['type']=='text'){
                $options_array[$ilab]['type'] = 'textfield';
            }
            if($opt['type']=='image'){
                $opt['type'] = 'upload';
                $opt['library_type'] = 'image';
            }
            if($opt['type']=='upload'){
                $options_array[$ilab]['type'] = 'dzs_add_media_att';
            }
        }
        if(isset($opt['sidenote'])){
            $options_array[$ilab]['description'] = $opt['sidenote'];
        }
        if(isset($opt['default'])){
            $options_array[$ilab]['std'] = $opt['default'];
            $options_array[$ilab]['default'] = $opt['default'];
        }
        if(isset($opt['options'])){
            $options_array[$ilab]['value'] = $opt['options'];
        }

        if(isset($opt['library_type'])){
            $options_array[$ilab]['library_type'] = $opt['library_type'];
        }

        if(isset($opt['class'])){
            $options_array[$ilab]['class'] = $opt['class'];
        }




        ?>
    <div class="setting" <?php

    if(isset($opt['dependency']) && $opt['dependency']){
        echo 'data-dependency=\''.json_encode($opt['dependency']).'\'';
    }


    ?> data-label="<?php echo $lab; ?>">
        <h4 class="setting-label"><?php echo $opt['title']; ?></h4>
        <div class="input-con type-<?php echo $opt['type']; ?>">
            <?php
            if($opt['type'] == 'text'){
                echo DZSHelpers::generate_input_text($lab, array(
                        'class'=>'shortcode-field  dzs-dependency-field',
                ));
            }
            if($opt['type'] == 'textarea_html'){
                $content = '';
                $editor_id = $lab;

                wp_editor( $content, $editor_id );
            }


            if($opt['type'] == 'quality_selecter'){

                echo '<input type="text" class="shortcode-field textinput upload-prev upload-type-video big-field" name="qualities" value=""/><a class=" button-secondary quick-edit-qualityarray" href="#" style="cursor:pointer;">'.esc_html__("Edit Qualities").'</a>';

            }
            if($opt['type'] == 'upload'){

                $upload_class = 'shortcode-field upload-target-prev upload-type-'.$opt['library_type'].' ';

                if(isset($opt['prefer_id']) && $opt['prefer_id']=='on'){
                    $upload_class.=' upload-get-id';
                }

                $upload_class.=' dzs-dependency-field';
                echo DZSHelpers::generate_input_text($lab, array(
                        'class'=>$upload_class,
                ));

                echo '<a href="#" class="button-secondary upload-for-target dzsvg-wordpress-uploader">'.esc_html__("Upload").'</a>';
            }
            if($opt['type'] == 'select'){
                echo DZSHelpers::generate_select($lab, array(
                        'class'=>'shortcode-field dzs-style-me skin-beige dzs-dependency-field',
                        'options'=>$opt['options'],
                ));

            }


            $oim = $opt;
            if(isset($oim['extra_html_after_input']) && $oim['extra_html_after_input']){
	            echo $oim['extra_html_after_input'];
            }
            ?>
        </div>
        <?php
        if(isset($opt['sidenote']) && $opt['sidenote']){
        ?><div class="sidenote"><?php echo $opt['sidenote']; ?></div><?php
        }
        ?>

        <?php
        if(isset($opt['sidenote-2']) && $opt['sidenote-2']){

?>            <div class="sidenote-2 <?php echo $opt['sidenote-2-class'] ?>"><?php echo $opt['sidenote-2']; ?></div>
            <?php
        }
        ?>


        </div><?php

        $ilab++;
    }

    echo '<br>';
    echo '<button class="button-primary submit-shortcode">'.esc_html__("Submit Shortcode").'</button>';


    ?><div class="shortcode-output"></div><?php

}
