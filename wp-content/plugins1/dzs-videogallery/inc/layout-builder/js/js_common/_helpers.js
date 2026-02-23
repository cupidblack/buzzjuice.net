export const getHtmlAttributeWithRegex = (attributeLabel, htmlInput) => {

  var result = null;

  var regexString = attributeLabel+'="([\\s|\\S]*?)"';
  console.log();
  var aux = (new RegExp(regexString)).exec(htmlInput);

  if(aux&&aux[1]){
    result = aux[1];
  }
  return result
}

export const serializeFormToJson = ($theForm_, $theOutput_) => {

  var foutObj = {
    items: [],
  };

  const assignToFinalObject = (foutItems, targetIndex, targetJsonObj) => {
    if (typeof foutItems[targetIndex] === 'undefined') {
      foutItems[targetIndex] = {};
    }
    foutItems[targetIndex] = Object.assign(foutItems[targetIndex], targetJsonObj)

  }
  $theForm_.querySelectorAll('*[data-json-structure]').forEach(function ($inputForm_) {
    var theValue = $inputForm_.value;
    try {
      var jsonStructure = $inputForm_.getAttribute('data-json-structure');

      if (jsonStructure) {

        // -- sanitize for json
        jsonStructure = jsonStructure.replace('the-value', theValue);
        var jsonObj = JSON.parse(jsonStructure);

        if (jsonStructure.indexOf('items') > 0) {
          jsonObj.items.forEach((thisVal, index) => {
            if (jsonObj.items[index]) {

              var firstKey = Object.keys(jsonObj.items[index])[0];


              if (typeof jsonObj.items[index][firstKey] === 'object') {
                assignToFinalObject(foutObj.items[index], firstKey, jsonObj.items[index][firstKey]);
              } else {
                assignToFinalObject(foutObj.items, index, jsonObj.items[index]);
              }
            }
          })

        } else {
          Object.keys(jsonObj).forEach(jsonKey => {
            if (!foutObj[jsonKey]) {
              foutObj[jsonKey] = {};
            }
            foutObj[jsonKey] = Object.assign(foutObj[jsonKey], jsonObj[jsonKey]);
          });
        }
      }

    } catch (err) {
      console.log('er r - ', err);
    }
  })
  return foutObj;
}