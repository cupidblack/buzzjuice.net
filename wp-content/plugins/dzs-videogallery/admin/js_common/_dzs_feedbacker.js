exports._feedbacker = null;
exports.timeoutClearFeedbackId = null;
exports.feedbacker_initSetup = function () {
  this._feedbacker = jQuery('.dzs-feedbacker').eq(0);
  this._feedbacker.fadeOut('fast');
}

exports.show_feedback = function (response, pargs) {



  var margs = {
    extra_class: ''
  }

  if (pargs) {
    margs = jQuery.extend(margs, pargs);
  }




  var ajaxMessage = '';
  var ajaxContainerClass = 'dzs-feedbacker';

  if(typeof response == 'object'){
    if(response['ajax_status']==='error'){
      ajaxContainerClass += ' is-error';
    }
    ajaxMessage = response['ajax_message'];
  }else{

    if (response.indexOf('success - ') === 0) {
      ajaxMessage = response.substr(10);
    }
    if (response.indexOf('error - ') === 0) {
      ajaxMessage = response.substr(8);
      ajaxContainerClass += ' is-error';
    }
  }

  if (margs.extra_class) {
    ajaxContainerClass += ' ' + margs.extra_class;
  }

  if(this._feedbacker){

    this._feedbacker.attr('class', ajaxContainerClass);

    this._feedbacker.html(ajaxMessage);
    this._feedbacker.fadeIn('fast');
  }

  var self = this;

  clearTimeout(this.timeoutClearFeedbackId);
  this.timeoutClearFeedbackId = setTimeout(function () {

    if(self._feedbacker) {
      self._feedbacker.fadeOut('slow');
    }
  }, 2000);

}
