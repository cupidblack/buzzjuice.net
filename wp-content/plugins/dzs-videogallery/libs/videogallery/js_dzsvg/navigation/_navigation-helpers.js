
/**
 * we need selfClass.navigation_customStructure
 * @param {DzsVideoGallery} selfClass
 * @return {string}
 */
export function playlist_navigationGenerateStructure(selfClass) {

  let desc = selfClass.navigation_customStructure;
  if(!desc){
    desc = '';
  }
  return desc;

}


/**
 *
 * @param {jQuery} $currentVideoPlayer
 * @param {string} structureMenuItemContentInner
 * @returns {string}
 */
export function playlist_navigationStructureAssignVars  ($currentVideoPlayer, structureMenuItemContentInner) {

  /**
   *
   * @param {string} currentStructure
   * @param {string} placeholderText
   * @param {string} argInStructure
   */
  function replaceInNav(currentStructure, placeholderText, argInStructure) {

    let feedValue = '';

    if($currentVideoPlayer.find(argInStructure).length){
      feedValue = $currentVideoPlayer.find(argInStructure).eq(0).html();
    }
    
    return currentStructure.replace(placeholderText, feedValue);
  }

  structureMenuItemContentInner = replaceInNav(structureMenuItemContentInner, '{{layout-builder.replace-title}}', '.feed-menu-title');
  structureMenuItemContentInner = replaceInNav(structureMenuItemContentInner, '{{layout-builder.replace-menu-description}}', '.feed-menu-desc');
  structureMenuItemContentInner = replaceInNav(structureMenuItemContentInner, '{{layout-builder.replace-thumbnail-url}}', '.feed-menu-image');


  return structureMenuItemContentInner;
}