<?php
// todo: html may be defined as object for bestest practice
return array(
  'builder_item'=>'<li class="dd-item dd3-item layout-builder--builder-item">
<div class="builder-layer--head "><i class="outline-outer"></i><i class="outline-outer--inner"></i><input type="hidden" name="{{inputName}}" value="{{inputValue}}"><span class="the-title" data-type="{{builderItemType}}">{{builderItemTitle}}</span><span class="sortable-handle-con dd-handle dd3-handle"><span class="sortable-handle fa fa-bars"></span></span></div>
<div class="builder-layer--content">
{{builderLayerFields}}
-><-<input type="text" data-lbkey="title" placeholder="input title todo" value="{{thevalue}}" default="4"/>-<>-
-><-<input type="text" data-lbkey="title2" placeholder="input title todo" value="{{thevalue}}" default="5"/>-<>-
</div>
</li>'
);