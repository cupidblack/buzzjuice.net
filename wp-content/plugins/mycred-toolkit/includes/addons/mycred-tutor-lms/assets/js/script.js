jQuery(document).ready(function () {

    jQuery(document).on( 'click', '.mycred-add-tutor_lms-hook', function(event) {
        var hook = jQuery(this).closest('.custom-hook-instance').clone();
        hook.find('input.mycred-tutor_lms-creds');
        hook.find('input.mycred-tutor_lms-log');
        hook.find('select.mycred-tutor_lms-dropdown_complete').val('0');
        hook.find('select.quiz_complete option:not([value="0"])').remove();
        hook.find('select.mycred_tutor_lms_dropdown_pass ').val('0');
        hook.find('select.quiz_pass option:not([value="0"])').remove();
        hook.find('select.mycred_tutor_lms_dropdown_fail').val('0');
        hook.find('select.quiz_fail option:not([value="0"])').remove();
        hook.find('select.mycred-tutor_lms-dropdown_course').val('0');
        hook.find('select.mycred-tutor_lms-dropdown_lesson option:not([value="0"])').remove();
        hook.find('select.mycred-tutor_lms-dropdown').val('0');
        jQuery(this).closest('.custom-hook-instance').after( hook );       
    }); 

    jQuery(document).on( 'click', '.mycred-remove-tutor_lms-hook', function() {    
        var container = jQuery(this).closest('.hook-instance');
        if ( container.find('.custom-hook-instance').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook instance?");
            if (dialog == true) {
                jQuery(this).closest('.custom-hook-instance').remove();
                ml_enable_disable_options( container.find('.custom-hook-instance'), '.mycred-tutor_lms-dropdown' );
                ml_enable_disable_options( container.find('.custom-hook-instance'), '.mycred-tutor_lms-dropdown_lesson' );
            } 
        }
    });

    jQuery(document).on('change' ,'.mycred-tutor_lms-dropdown_complete', function(){

        var _this = jQuery(this);
        var value = _this.val();
        var data = {
            'action': 'mycred_specific_course_for_quiz',
            quiz: value
        };

        jQuery.post( ajaxurl, data, function( response ) {      
            response = JSON.parse( response );
            var ele = _this.closest('.custom-hook-instance').find('.quiz_complete');
            var counter = 0;
            var selected = [];
            var current_course = [];
            var container = _this.closest('.hook-instance');
            
            container.find('.quiz_complete').not(ele).each(function () {
                selected.push( jQuery(this).val() );
                current_course.push( jQuery(this).prev().val() );
            });

            var allquizcompleteoption = '<option value="0">All Quiz</option>';

            jQuery.each( current_course.map((e, i) => e === value ? i : '').filter(String), function( key, val ){

                if ( selected[val] == 0 )
                    allquizcompleteoption = '<option value="0" disabled>All Quiz</option>';

            });
                
            ele.html( allquizcompleteoption );

            jQuery.each( response, function(){

                if ( ! selected.includes( response[counter][0] ) ) {
                    ele.append( '<option value=' + response[counter][0] + '>' + response[counter][1] + '</option>' );
                }
                else {
                    ele.append( '<option value=' + response[counter][0] + ' disabled>' + response[counter][1] + '</option>' );
                }
                counter++;
            } );
        });
    });


    jQuery(document).on('change' ,'.mycred_tutor_lms_dropdown_pass' ,function(){
        var _this = jQuery(this);
        var value = _this.val();
        var data = {
            'action': 'mycred_specific_course_for_quiz',
            quiz: value
        };

        jQuery.post( ajaxurl, data, function( response ) { 
            response = JSON.parse( response );
            var ele = _this.closest('.custom-hook-instance').find('.quiz_pass');
            var counter = 0;
            var selected = [];
            var current_course = [];
            var container = _this.closest('.hook-instance');
            
            container.find('.quiz_pass').not(ele).each(function () {
                selected.push( jQuery(this).val() );
                current_course.push( jQuery(this).prev().val() );
            });

            var allquizpassoption = '<option value="0">All Quiz</option>';

            jQuery.each( current_course.map((e, i) => e === value ? i : '').filter(String), function( key, val ){

                if ( selected[val] == 0 )
                    allquizpassoption = '<option value="0" disabled>All Quiz</option>';

            });
                
            ele.html( allquizpassoption );

            jQuery.each( response, function(){

                if ( ! selected.includes( response[counter][0] ) ) {
                    ele.append( '<option value=' + response[counter][0] + '>' + response[counter][1] + '</option>' );
                }
                else {
                    ele.append( '<option value=' + response[counter][0] + ' disabled>' + response[counter][1] + '</option>' );
                }
                counter++;
            });
        });
    });

    jQuery(document).on('change' ,'.mycred_tutor_lms_dropdown_fail' ,function(){
        var _this = jQuery(this);
        var value = _this.val();
        var data = {
            'action': 'mycred_specific_course_for_quiz',
            quiz: value
        };

        jQuery.post( ajaxurl, data, function( response ) {     
            response = JSON.parse( response );
            var ele = _this.closest('.custom-hook-instance').find('.quiz_fail');
            var counter = 0;   
            var selected = [];
            var current_course = [];
            var container = _this.closest('.hook-instance');
            
            container.find('.quiz_fail').not(ele).each(function () {
                selected.push( jQuery(this).val() );
                current_course.push( jQuery(this).prev().val() );
            });

            var allquizfailoption = '<option value="0">All Quiz</option>';

            jQuery.each( current_course.map((e, i) => e === value ? i : '').filter(String), function( key, val ){

                if ( selected[val] == 0 )
                    allquizfailoption = '<option value="0" disabled>All Quiz</option>';

            });
                
            ele.html( allquizfailoption );

            jQuery.each( response, function(){

                if ( ! selected.includes( response[counter][0] ) ) {
                    ele.append( '<option value=' + response[counter][0] + '>' + response[counter][1] + '</option>' );
                }
                else {
                    ele.append( '<option value=' + response[counter][0] + ' disabled>' + response[counter][1] + '</option>' );
                }
                counter++;
            } );
        });
    });

    jQuery(document).on('change' ,'.mycred-tutor_lms-dropdown_course' ,function(){        
        var _this = jQuery(this);
        var value = _this.val();
        var data = {
            'action': 'mycred_specific_course_for_lesson',
            lesson: value
        };
       
        jQuery.post( ajaxurl, data, function( response ) { 

            response = JSON.parse( response );
            var ele = _this.closest('.custom-hook-instance').find('.mycred-tutor_lms-dropdown_lesson'); 
            var counter = 0;
            var selected = [];
            var current_course_lessons = [];
            var container = _this.closest('.hook-instance');
            
            container.find('.mycred-tutor_lms-dropdown_lesson').not(ele).each(function () {
                selected.push( jQuery(this).val() );
                current_course_lessons.push( jQuery(this).prev().val() );
            });

            var allLessonsOption = '<option value="0">All Lesson</option>';

            jQuery.each( current_course_lessons.map((e, i) => e === value ? i : '').filter(String), function( key, val ){

                if ( selected[val] == 0 )
                    allLessonsOption = '<option value="0" disabled>All Lesson</option>';

            });
                
            ele.html( allLessonsOption );

            jQuery.each( response, function(){

                if ( ! selected.includes( response[counter][0] ) ) {
                    ele.append( '<option value='+ response[counter][0] +'>' + response[counter][1] + '</option>' );
                }
                else {
                    ele.append( '<option value=' + response[counter][0] + ' disabled>' + response[counter][1] + '</option>' );
                }
                counter++;
            });
        });
    });

    jQuery(document).on('change', '.quiz_complete', function(){
         ml_enable_disable_options( jQuery(this), '.quiz_complete' )
    });

    jQuery(document).on('change', '.quiz_pass', function(){
         ml_enable_disable_options( jQuery(this), '.quiz_pass' )
    });

    jQuery(document).on('change', '.quiz_fail', function(){
         ml_enable_disable_options( jQuery(this), '.quiz_fail' )
    });

    jQuery(document).on('change', '.mycred-tutor_lmsdropdown_lesson', function(){
         ml_enable_disable_options( jQuery(this), '.mycred-tutor_lms-dropdown_lesson' )
    });

    jQuery(document).on('change' ,'.mycred-tutor_lms-dropdown' ,function(){
        ml_enable_disable_options( jQuery(this), '.mycred-tutor_lms-dropdown' )
    });

    jQuery(document).on('click' ,'.mycred-add-course-enroll' ,function(){
         ml_enable_disable_options( jQuery(this).closest('.custom-hook-instance').find('select.mycred-tutor_lms-dropdown' ), '.mycred-tutor_lms-dropdown' );
    });

});

function ml_enable_disable_options( course, eleClass ) {
 
    var selected = [];
    var container = course.closest('.hook-instance');
    container.find('select'+ eleClass).each(function () {
        container.find('select'+ eleClass).not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
        selected.push( jQuery(this).val() );
    });

    container.find('option').each(function () {
        
        if( ! selected.includes( jQuery(this).attr('value')) ) {
            container.find('select'+ eleClass).find('option[value="'+jQuery(this).val()+'"]').removeAttr('disabled');
        }

    });

}
