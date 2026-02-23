<?php
// todo: html may be defined as object for bestest practice
const DEFAULT_CONFIG_SKIN_BOXY ='<dfn class="feed-layout-builder--menu-items feed-dzsvg2 feed-temp-for-skin-boxy" hidden>
  <div class="layout-builder--menu-items--layout-custom-customid layout-builder--main-con">
    <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container">
      <div class="layout-builder--item layout-builder--item--2312321 layout-builder--item--type-thumbnail navigation-type-image" style="background-image: url({{layout-builder.replace-thumbnail-url}})"></div>
      <div class="layout-builder--item center-it has-play-button-inside layout-builder--item--3314421 layout-builder--item--type-misc" style=";"></div>
    </div>
  </div>
</dfn>';
const DEFAULT_CONFIG_LAYOUT_BUILDER ='<dfn class="feed-layout-builder--menu-items feed-dzsvg2" hidden>
  <div class="layout-builder--menu-items--layout-custom-customid layout-builder--main-con">
    <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container">
    <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="padding-top: 75%;">
      <div class="layout-builder--item layout-builder--item--2312321 layout-builder--item--type-thumbnail navigation-type-image" style="position:absolute; top:0; left:0; width: 100%; height: 100%; background-image: url({{layout-builder.replace-thumbnail-url}})"></div>
      </div>
      <div class="layout-builder--item center-it has-play-button-inside layout-builder--item--3314421 layout-builder--item--type-misc" style=";"></div>
    </div>
    <div class="layout-builder--item layout-builder--item--3321321 layout-builder--item--type-title" style=";">{{layout-builder.replace-title}}</div>
    <div class="layout-builder--item layout-builder--item--21312321 layout-builder--item--type-menu-description" style=";">{{layout-builder.replace-menu-description}}</div>
  </div>
</dfn>';



const DEFAULT_CONFIG_WALL = '<div hidden class="feed-layout-builder--menu-items feed--feed-layout-builder--menu-items--for-wall ">
<div class="layout-builder--structure  layout-builder--main-con layout-builder--menu-items--layout-default ">
  <div class="layout-builder--item layout-builder--item--11241412321 layout-builder--item--type-container" style="padding-top: 75%;">
  
  <div class="layout-builder--item layout-builder--item--11241412323 layout-builder--item--type-container" style="position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;">
    <div class="layout-builder--item layout-builder--item--2312321 layout-builder--item--type-thumbnail navigation-type-image" style="background-image: url({{layout-builder.replace-thumbnail-url}}); 
width: calc(100% + 20px);
    height: calc(100% + 9px);
    position: absolute;
    top: 0;
    left: 0;
    padding-top: 0;
    margin-left: -10px;
    margin-top: -10px;
}"></div>
  </div>  
  </div>
  <div class="layout-builder--item layout-builder--item--11241412322 layout-builder--item--type-container" style="margin-top: 20px; margin-bottom: 10px;">
    <div class="layout-builder--item layout-builder--item--3321321 layout-builder--item--type-title" style=";">{{layout-builder.replace-title}}</div>
    <div class="layout-builder--item layout-builder--item--21312321 layout-builder--item--type-menu-description" style=";">{{layout-builder.replace-menu-description}}</div>
  </div>
</div>
</div>';
