export const viewGenerateStructureItemMeta = ($feedItem) => {

  let aux_struct_item_meta = '';
  aux_struct_item_meta += ' <div class="item-meta">';


  if ($feedItem.find('.feed-zfolio-the-title').length) {

    aux_struct_item_meta += ' <div class="the-title">' + $feedItem.find('.feed-zfolio-the-title').eq(0).html() + '</div>';
  }

  if ($feedItem.find('.feed-zfolio-the-desc').length) {

    aux_struct_item_meta += ' <div class="the-desc">' + $feedItem.find('.feed-zfolio-the-desc').eq(0).html() + '</div>';
  }


  aux_struct_item_meta += '</div>';

  return aux_struct_item_meta;
}