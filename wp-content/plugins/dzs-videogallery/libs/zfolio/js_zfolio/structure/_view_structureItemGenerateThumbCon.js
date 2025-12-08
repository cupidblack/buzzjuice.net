export const structureItemGenerateThumbCon = (item_link_thumb_con_to, $feedItem) => {

  let stringStructTheFeatureCon = '';


  if (item_link_thumb_con_to === 'link') {
    stringStructTheFeatureCon += ' <a class="the-feature-con  custom-a';
  } else {
    stringStructTheFeatureCon += ' <div class="the-feature-con';
  }


  if (item_link_thumb_con_to === 'ultibox') {

    if ($feedItem.attr('data-bigimage')) {
      stringStructTheFeatureCon += ' ultibox-item';
    }
  }

  stringStructTheFeatureCon += '"';


  if (item_link_thumb_con_to === 'ultibox') {


    if ($feedItem.attr('data-bigimage')) {

      stringStructTheFeatureCon += ' data-source="' + $feedItem.attr('data-bigimage') + '"';
    }
    if ($feedItem.attr('data-biggallery')) {

      stringStructTheFeatureCon += ' data-biggallery="' + $feedItem.attr('data-biggallery') + '"';
    }
  }
  if (item_link_thumb_con_to === 'link') {

    if ($feedItem.attr('data-link')) {

      stringStructTheFeatureCon += ' href="' + $feedItem.attr('data-link') + '"';

      if ($feedItem.attr('data-link-target')) {

        stringStructTheFeatureCon += ' target="' + $feedItem.attr('data-link-target') + '"';
      }
    }
  }
  stringStructTheFeatureCon += '>';


  if (item_link_thumb_con_to === 'link') {
    stringStructTheFeatureCon += ' </a>';
  } else {
    stringStructTheFeatureCon += '</div>';
  }

  return stringStructTheFeatureCon;
}