import {documentReady, $es} from "../shared/esjquery/js/_esjquery";

const multiSharer_init = () => {


  const $socialBox = $es('.close-btn-con, .dzs-social-box--main-con');



  $es(document).on('click', '.dzs-social-box--main-con .close-btn-con,.dzs-social-box--main-con .overlay-background', function(e){


    var _c = jQuery('.dzsvg-main-con').eq(0);

    $socialBox.removeClass('loading-item loaded-item');
  })




  $es(document).on('click', '.field-for-view', function () {


    (this).select();
    document.execCommand('copy');

  });
}


documentReady(()=>{
  multiSharer_init();
})