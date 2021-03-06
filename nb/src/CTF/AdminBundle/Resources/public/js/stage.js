$(document).ready(function() {
    
    $('#stage-list-container').on('click', '#add-more-stages', function(e) {
        e.preventDefault();
        
        $.get(Routing.generate('ctf_admin_stage_form', null), null, function(data) {
            $('#stage-form-dyn').empty();
            $('#stage-form-dyn').html(data).fadeIn();
        });
    });
    
    $('#stage-list-container').on('click', 'a.list-item', function(e) {
        e.preventDefault();
        
        $.get($(this).attr('href'), null, function(data) {
            $('#stage-form-dyn').empty();
            $('#stage-form-dyn').html(data).fadeIn();
        });
    });
    
    $('#stage-form-container').on('click', '#poof-stage', function(e) {
        e.preventDefault();
        
        $.get($(this).attr('data-url'), null, function(data) {
            var response = JSON.parse(data);
            $('#stage-form-dyn').empty();
            
            if ('success' == response.result) {
                $('#stage-form-status').html('<div class="alert alert-success">' + response.message + '</div>').show().fadeOut(2200);
                
                $.get(Routing.generate('ctf_admin_stage_list', null), null, function(data) {
                    $('#stage-list-dyn').html(data).show();
                });
                $.get(Routing.generate('ctf_admin_stage_list', { 'q': 1 }), null, function(data) {
                    $('#qlist-dyn').html(data).show();
                });
            } else {
                $('#stage-form-status').html('<div class="alert alert-error">' + response.message + '</div>').show().fadeOut(2200);
            }
        });
    });
    
    $('#stage-list-container').on('click', 'button', function(event) {
        event.preventDefault();
        $('#stage-form-dyn').html('<div class="alert alert-error"><button class="close" data-dismiss="alert" type="button">&times;</button><h4 class="alert-heading">Are you sure?</h4><p>You are about to delete a stage.</p><a class="btn btn-danger" id="poof-stage" data-url="' + $(this).attr('data-url') + '">Poof, delete it!</a>&nbsp;<button class="btn" data-dismiss="alert">Cancel</button></div>').show();
        
        return false;
    });
    
    $('#stage-form-container').on('submit', '#stage-form', function(e) {
        e.preventDefault();
        
        $.post($('#stage-form').attr('action'), $('#stage-form').serialize(), function(data) {
            if ('true' == data) {
                $('#stage-form').fadeOut();
                $('#stage-form').fadeIn('fast', function() {
                    $('#ctf_questbundle_stagetype_name').val('');
                    $('#ctf_questbundle_stagetype_description').val('');
                });
                
                $('#stage-form-status').html('<div class="alert alert-success">Successfully Saved!</div>').show().fadeOut(2200);
                
                $.get(Routing.generate('ctf_admin_stage_list', null), null, function(data) {
                    $('#stage-list-dyn').html(data).show();
                });
                $.get(Routing.generate('ctf_admin_stage_list', { 'q': 1 }), null, function(data) {
                    $('#qlist-dyn').html(data).show();
                });
            } else {
                $('#stage-form-status').html('<div class="alert alert-error">Could not save!</div>').show().fadeOut(1200);
            }
        });
    });
    
    $('#qlist-container').on('click', 'a.list-item', function(e) {
        e.preventDefault();
        
        $.get($(this).attr('href'), null, function(data) {
            $('#question-form-dyn').empty();
            $('#question-form-dyn').html(data).fadeIn();
            tinyMCE.init({
                selector: ".rich",
                mode : "specific_textareas"
            });
        });
    });
    
    $('#qlist-container').on('click', '#add-more-questions', function(e) {
        e.preventDefault();
        
        $.get(Routing.generate('ctf_admin_stage_question', null), null, function(data) {
            $('#question-form-dyn').empty();
            $('#question-form-dyn').html(data).show();
            tinyMCE.init({
                selector: ".rich",
                mode : "specific_textareas"
            });
            $('#qtabs').tab();
        });
    });
    
    $('#question-form-container').on('click', '#poof-question', function(e) {
        e.preventDefault();
        
        $.get($(this).attr('data-url'), null, function(data) {
            var response = JSON.parse(data);
            $('#question-form-dyn').empty();
            
            if ('success' == response.result) {
                $('#question-form-dyn').html('<div class="alert alert-success">' + response.message + '</div>').show().fadeOut(2200);

                $.get(Routing.generate('ctf_admin_stage_list', { 'q': 1 }), null, function(data) {
                    $('#qlist-dyn').html(data).show();
                });
            } else {
                $('#question-form-dyn').html('<div class="alert alert-error">' + response.message + '</div>').show().fadeOut(2200);
            }
        });
    });
    
    $('#qlist-container').on('click', 'button', function(e) {
        e.preventDefault();
        
        $('#question-form-dyn').html('<div class="alert alert-error"><button class="close" data-dismiss="alert" type="button">&times;</button><h4 class="alert-heading">Are you sure?</h4><p>You are about to delete a question.</p><a class="btn btn-danger" id="poof-question" data-url="' + $(this).attr('data-url') + '">Poof, delete it!</a>&nbsp;<button class="btn" data-dismiss="alert">Cancel</button></div>').show();
        
        return false;
    });
});
