
/**
 *
 * @param newSliderOption
 * @param {object} props
 * @param sliders
 * @param newSliders
 * @param newSliderOption.label - slider label
 * @param newSliderOption.slug - slider slug
 */
export function inlineUltiboxEdit(newSliderOption, props, sliders, newSliders) {

  var swFound = false;
  for (var key in newSliders) {
    if (newSliders[key].value === newSliderOption.value) {
      swFound = true;
    }
  }
  if (swFound === false) {
    sliders.push(newSliderOption);
    console.log('pushed .. ', newSliderOption, 'sliders - ', sliders);
  }
  props.setAttributes({dzsvg_select_id: newSliderOption.value})

  var $ = jQuery;
  $('.dzsulb-main-con').removeClass('loaded-item loading-item');

  setTimeout(() => {

    window.close_ultibox();
  }, 3000);
}

