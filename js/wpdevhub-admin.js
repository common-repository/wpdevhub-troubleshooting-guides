
jQuery( document ).ready( function( $ ) {

    // Restore the main ID when the add media button is pressed
    jQuery( "a.add_media" ).on( "click", function() {
        wp.media.model.settings.post.id = DSCF_DTG_Admin.wp_media_post_id;
    });

    try{
        jQuery(".DSCF_DTG_jquery-ui-sortable").sortable();
        jQuery(".DSCF_DTG_jquery-date-picker").datepicker();
        jQuery(".DSCF_DTG_jquery-datetime-picker").datetimepicker();
    }catch(e){}

    // Load User Messages
    DSCF_DTG_UserMessages.load();

});

var DSCF_DTG_Admin = {

    // Uploading files
    wp_media_post_id:0, // Store the old id
    blankCounter:0,
    dttListBlankCounter:0,

    openMediaLibrary:function( objectName , set_to_post_id ){
        event.preventDefault();

        var file_frame;

        // Store the Old ID
        DSCF_DTG_Admin.wp_media_post_id = wp.media.model.settings.post.id;


        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            // Set the post ID to what we want
            file_frame.uploader.uploader.param( "post_id", set_to_post_id );
            // Open frame
            file_frame.open();
            return;
        } else {
            // Set the wp.media post id so the uploader grabs the ID we want when initialised
            wp.media.model.settings.post.id = set_to_post_id;
        }
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: "Select a image to upload",
            button: {
                text: "Use this image"
            },
            multiple: false	// Set to true to allow multiple files to be selected
        });
        // When an image is selected, run a callback.
        file_frame.on( "select", function() {
            // We set multiple to false so only get one image from the uploader
            attachment = file_frame.state().get("selection").first().toJSON();
            // Do something with attachment.id and/or attachment.url here
            jQuery( "#"+objectName+"_preview" ).attr( "src", attachment.url ).css( "width", "auto" );
            jQuery( "#"+objectName+"_filename" ).html(attachment.id);
            jQuery( "#"+objectName+"" ).val( attachment.id );    // Our form handler will pick this one up
            // Restore the main post ID
            wp.media.model.settings.post.id = DSCF_DTG_Admin.wp_media_post_id;
        });
        // Finally, open the modal
        file_frame.open();
    },

    openMediaLibraryMultiple:function( objectName ){
        event.preventDefault();

        var file_frame;

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            // Set the post ID to what we want
            file_frame.uploader.uploader.param( "post_id" );
            // Open frame
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: "Select a image to upload",
            button: {
                text: "Use this image"
            },
            multiple: true	// Set to true to allow multiple files to be selected
        });
        // When an image is selected, run a callback.
        file_frame.on( "select", function() {

            var selection = file_frame.state().get("selection");
            selection.map( function( attachment ) {
                attachment = attachment.toJSON();

                var url_images = DSCF_DTG_vars.url+"/images/";
                var wrapperId = objectName+"_wrapper_"+attachment.id;

                // Add the preview
                var html = '<div id="'+wrapperId+'" class="dtg-ml-thumb-wrapper">' +
                    '<div class="dtg-align-right"><img src="'+url_images+'/cancel.png" style="width:14px;" onclick="jQuery(\'#'+wrapperId+'\').remove()" /></div>' +
                    '<img src="'+attachment.url+'" width="100" height="100">' +
                    '<input type="hidden" name="'+objectName+'_mediaId_'+attachment.id+'" id="'+objectName+'_mediaId_'+attachment.id+'" value="'+attachment.id+'" />' +
                    '</div>';
                jQuery( "#"+objectName+"_preview" ).append( html );


            });

        });
        // Finally, open the modal
        file_frame.open();
    },

    openMediaLibrarySmdMultiple:function( objectName ){
        event.preventDefault();

        var file_frame;

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            // Set the post ID to what we want
            file_frame.uploader.uploader.param( "post_id" );
            // Open frame
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: "Select a image to upload",
            button: {
                text: "Use this image"
            },
            multiple: true	// Set to true to allow multiple files to be selected
        });
        // When an image is selected, run a callback.
        file_frame.on( "select", function() {

            var selection = file_frame.state().get("selection");
            selection.map( function( attachment ) {
                attachment = attachment.toJSON();

                var url_images = DSCF_DTG_vars.url+"/images/";
                var wrapperId = objectName+"_wrapper_"+attachment.id;

                // Add the preview
                /*
                var html = '<li id="'+wrapperId+'" class="dtg-ml-row">' +
                    '<div class="dtg-align-right"><img src="'+url_images+'/cancel.png" style="width:14px;" onclick="jQuery(\'#'+wrapperId+'\').remove()" /></div>' +
                    '<div class="dtg-inline-block"><img src="'+attachment.url+'" width="100" height="100"></div>' +
                    '<textarea style="height:100px; width:400px;" name="'+objectName+'_caption_'+attachment.id+'" id="'+objectName+'_caption_'+attachment.id+'"></textarea>' +
                    '<input type="hidden" name="'+objectName+'_smdMediaId_'+attachment.id+'" id="'+objectName+'_smdMediaId_'+attachment.id+'" value="'+attachment.id+'" />' +
                    '</div>';
                jQuery( "#"+objectName+"_preview" ).append( html );
                */

                var html = '<li id="'+wrapperId+'">' +
                    '<table id="'+objectName+'_table" class="dtg-table dtg-table-striped dtg-table-padding-mini smdDiveReportTable">' +
                    '<tr>' +
                    '<th>Image Title</th>' +
                    '<td><input type="text" name="'+objectName+'_title_'+attachment.id+'" size="50" /></td>' +
                    '<td class="dtg-align-right"><button type=button onclick="jQuery(\'#'+wrapperId+'\').remove();">delete</button></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th>Camera Settings</th>' +
                    '<td><input type="text" name="'+objectName+'_cameraSettings_'+attachment.id+'" size="50" /></td>' +
                    '<td rowspan="2" class="dtg-align-center"><img src="'+attachment.url+'" width="150" height="150" /></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th>Comments</th>' +
                    '<td><textarea class="dtg-textarea" style="height:100px;" name="'+objectName+'_caption_'+attachment.id+'" id="'+objectName+'_caption_'+attachment.id+'"></textarea></td>' +
                    '</tr>' +
                    '</table>' +
                    '<input type="hidden" name="'+objectName+'_smdMediaId_'+attachment.id+'" id="'+objectName+'_smdMediaId_'+attachment.id+'" value="'+attachment.id+'" />' +
                    '<hr class="wpdevhub-hr-6" />' +
                    '</li>';
                jQuery( "#"+objectName+"_preview" ).append( html );
            });

        });
        // Finally, open the modal
        file_frame.open();
    },
/*
    addSmdDiveReport:function(editorKeyname){
        this.blankCounter++;
        var keynamePrefix = editorKeyname+'_new_'+this.blankCounter;
        var html = '<div id="'+keynamePrefix+'_wrapper">' +
        '<table id="'+keynamePrefix+'_table" class="dtg-table dtg-table-striped dtg-table-padding-mini smdDiveReportTable">' +
        '<tr>' +
        '<th class="dtg-align-right">Dive Title</th>' +
        '<td colspan="4"><input type="text" name="'+keynamePrefix+'_title" size="50" /></td>' +
        '<td class="dtg-align-right"><button type=button onclick="DSCF_DTG_Admin.deleteSmdDiveReport(\''+keynamePrefix+'_wrapper\')">delete</button></td>' +
        '</tr>' +
        '<tr>' +
        '<th class="dtg-align-right">Dive Rating</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_diveRating" size="10" /></td>' +
        '<th class="dtg-align-right">Dive Depth</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_diveDepth" size="10" /></td>' +
        '<th class="dtg-align-right">Exposure Protection</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_exposureProtectionId" size="10" /></td>' +
        '</tr>' +
        '<tr>' +
        '<th class="dtg-align-right">Visibility</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_visibility" size="10" /></td>' +
        '<th class="dtg-align-right">Bottom Time</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_bottomTime" size="10" /></td>' +
        '<th class="dtg-align-right">Weight Used</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_weight" size="10" /></td>' +
        '</tr>' +
        '<tr>' +
        '<th class="dtg-align-right">Surface Temp</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_temperatureSurface" size="10" /></td>' +
        '<th class="dtg-align-right">Tank PSI Start</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_tankPsiStart" size="10" /></td>' +
        '<td></td>' +
        '<td></td>' +
        '</tr>' +
        '<tr>' +
        '<th class="dtg-align-right">Bottom Temp</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_temperatureBottom" size="10" /></td>' +
        '<th class="dtg-align-right">Tank PSI End</th>' +
        '<td><input type="text" name="'+keynamePrefix+'_tankPsiEnd" size="10" /></td>' +
        '<td></td>' +
        '<td></td>' +
        '</tr>' +
        '<tr>' +
        '<th class="dtg-align-right">Comments</th>' +
        '<td colspan="5"><textarea name="'+keynamePrefix+'_comments" class="dtg-textarea"></textarea></td>' +
        '</tr>' +
        '</table>' +
        '<input type="hidden" name="'+editorKeyname+'_id_new_'+this.blankCounter+'" value="new_'+this.blankCounter+'" />' +
        '<hr class="wpdevhub-hr-6" />' +
        '</div>';
        jQuery("#smdDiveReportsWrapper").append(html);
    },

    deleteSmdDiveReport:function(objectName){
        jQuery("#"+objectName).remove();
    },
*/
    addBlankRow:function(objectName, beforeId, includeCheckbox){
        this.blankCounter++;
        var textObjectName = objectName+'_blankTxt_'+this.blankCounter;
        var html = '<div id="'+textObjectName+'_wrapper">';
        if(includeCheckbox){
            html += '<input type="checkbox" name="'+objectName+'_blankChk_'+this.blankCounter+'" checked="checked" />';
        }
        html += '<input type="text" name="'+textObjectName+'" value="" size="50" /><button type=button onclick="DSCF_DTG_Admin.deleteOptionRow(\''+textObjectName+'\')">delete</button>';
        html += '</div>';
        jQuery("#"+beforeId).before(html);
    },

    deleteOptionRow:function(objectName){
        jQuery("#"+objectName).val("");
        jQuery("#"+objectName+"_wrapper").toggle();
    },
    // Utility function to shuffle an array
    shuffleArray:function(o){
        for(var j, x, i = o.length; i; j = parseInt(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
        return o;
    },
    deleteGenericWrapperRow:function(objectName){
        jQuery("#"+objectName+"_wrapper").remove();
    },

    addDttListRow:function(objectName, beforeId){
        // Set the counter
        var counter = jQuery('#'+objectName+'_row_counter').val();
        counter = counter + 1;
        jQuery('#'+objectName+'_row_counter').val(counter);

        var placeholder = DSCF_DTG_vars.url+"/images/document_vertical.png";

        // Setup other data and build the row
        var rowId = objectName + '_dtt_row_' + counter;
        var rowKey = objectName + '_dtt_item_' + counter;
        var html = '';
        html += '<div id="'+rowKey+'_wrapper" class="dtg-pbox5">';
        html += '<div>';
        html += '<button style="float:right;" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''+rowKey+'\');">Delete Item</button>';
        html += '<input type="hidden" name="'+rowId+'" value="'+counter+'" />';
        html += 'Title: <input type="text" name="'+rowKey+'_title" value="" size="50" />';
        html += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Primary Image: ';
        html += '<img id="'+rowKey+'_mediaId_preview" src="'+placeholder+'" style="width:50px; height:50px; padding:10px; vertical-align:middle;" />';
        html += '<button onclick="DSCF_DTG_Admin.openMediaLibrary(\''+rowKey+'_mediaId\', 0)">Open Media Library</button>';
        html += '<input type="hidden" name="'+rowKey+'_mediaId" id="'+rowKey+'_mediaId" value="0">';
        html += '<a href="javascript:DSCF_DTG_Admin.removeDttImage(\''+rowKey+'\');">remove</a>';
        html += '</div>';

        html += '<div><textarea id="'+rowKey+'_contents" name="'+rowKey+'_contents" style="width:100%; height:150px;"></textarea></div>';

        html += '</div>'; // Wrapper

        jQuery("#"+beforeId).before(html);

        //tinymce.execCommand( 'mceAddEditor', true, rowKey+'_contents' );
    },
    removeDttImage:function(rowKey){
        var placeholder = DSCF_DTG_vars.url+"/images/document_vertical.png";
        jQuery("#"+rowKey+"_mediaId_preview").attr("src", placeholder);
        jQuery("#"+rowKey+"_mediaId").val(0);
    },
    addWdhPluginFeatureRow:function(objectName, beforeId){
        // Set the counter
        var counter = jQuery('#'+objectName+'_row_counter').val();
        counter = counter + 1;
        jQuery('#'+objectName+'_row_counter').val(counter);

        var placeholder = DSCF_DTG_vars.url+"/images/document_vertical.png";

        // Setup other data and build the row
        var rowId = objectName + '_wdh_row_' + counter;
        var rowKey = objectName + '_wdh_item_' + counter;
        var html = '';
        html += '<div id="'+rowKey+'_wrapper" class="dtg-pbox5">';
        html += '<div>';
        html += '<button style="float:right;" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''+rowKey+'\');">Delete Feature</button>';
        html += '<input type="hidden" name="'+rowId+'" value="'+counter+'" />';
        html += 'Title: <input type="text" name="'+rowKey+'_title" value="" size="50" />';
        html += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Primary Image: ';
        html += '<img id="'+rowKey+'_mediaId_preview" src="'+placeholder+'" style="width:50px; height:50px; padding:10px; vertical-align:middle;" />';
        html += '<button onclick="DSCF_DTG_Admin.openMediaLibrary(\''+rowKey+'_mediaId\', 0)">Open Media Library</button>';
        html += '<input type="hidden" name="'+rowKey+'_mediaId" id="'+rowKey+'_mediaId" value="0">';
        html += '<a href="javascript:DSCF_DTG_Admin.removeWdhPluginFeatureImage(\''+rowKey+'\');">remove</a>';
        html += '</div>';

        html += '<div><textarea id="'+rowKey+'_contents" name="'+rowKey+'_contents" style="width:100%; height:150px;"></textarea></div>';

        html += '</div>'; // Wrapper

        jQuery("#"+beforeId).before(html);

        //tinymce.execCommand( 'mceAddEditor', true, rowKey+'_contents' );
    },
    removeWdhPluginFeatureImage:function(rowKey){
        var placeholder = DSCF_DTG_vars.url+"/images/document_vertical.png";
        jQuery("#"+rowKey+"_mediaId_preview").attr("src", placeholder);
        jQuery("#"+rowKey+"_mediaId").val(0);
    },
    addDtgStepRow:function(){
        // Set the counter
        var counter = jQuery('#dtg_row_counter').val();
        counter = parseInt(counter);
        counter = counter + 1;
        jQuery('#dtg_row_counter').val(counter);

        // Setup other data and build the row
        var rowId = 'dtg_row_' + counter;
        var rowKey = 'dtg_step_' + counter;
        var html = '';
        html += '<div id="'+rowKey+'_wrapper" class="dtg-pbox5">';
        html += '<p>';
        html += '<button style="float:right;" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''+rowKey+'\');">Delete Item</button>';
        html += '<input type="hidden" name="'+rowId+'" value="'+counter+'" />';
        html += 'Step Title: <input type="text" name="'+rowKey+'_title" value="" size="50" />';
        html += '</p>';

        html += '<div><textarea id="'+rowKey+'_contents" name="'+rowKey+'_contents"></textarea></div>';

        html += '</div>'; // Wrapper

        jQuery("#addMoreDtgStepRows").before(html);

        tinymce.execCommand( 'mceAddEditor', true, rowKey+'_contents' );
    },

    addDrcIngredientRow:function(){

        // Get the ingredient measurements
        var measurements = DSCF_DTG_extra_vars.measurements;

        // Set the counter
        var counter = jQuery('#drc_ingredient_row_counter').val();
        counter = parseInt(counter);
        counter = counter + 1;
        jQuery('#drc_ingredient_row_counter').val(counter);

        // Setup other data and build the row
        var rowId = 'drc_ingredient_row_' + counter;
        var rowKey = 'drc_ingredient_' + counter;
        var html = '';
        html += '<div id="'+rowKey+'_wrapper" class="dtg-pbox5">';
        html += '<p>';
        html += '<button style="float:right;" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''+rowKey+'\');">Delete Item</button>';
        html += '<input type="hidden" name="'+rowId+'" value="'+counter+'" />';
        html += '<input type="text" name="'+rowKey+'_quantity" value="" size="10" placeholder="qty" />';
        html += '<select id="'+rowKey+'_measurement" name="'+rowKey+'_measurement">';
        for(s in measurements){
            var measurement = measurements[s];
            html += '<option value="'+s+'">'+measurement+'</option>';
        }
        html += '</select>';
        html += '<input type="text" name="'+rowKey+'_title" value="" size="50" placeholder="ingredient" />';
        html += '</p>';

        html += '</div>'; // Wrapper

        jQuery("#addMoreDrcIngredientRows").before(html);

        //tinymce.execCommand( 'mceAddEditor', true, rowKey+'_contents' );
    },

    addDrcInstructionRow:function(){
        // Set the counter
        var counter = jQuery('#drc_instruction_row_counter').val();
        counter = parseInt(counter);
        counter = counter + 1;
        jQuery('#drc_instruction_row_counter').val(counter);

        // Setup other data and build the row
        var rowId = 'drc_instruction_row_' + counter;
        var rowKey = 'drc_instruction_' + counter;
        var html = '';
        html += '<div id="'+rowKey+'_wrapper" class="dtg-pbox5">';
        html += '<p>';
        html += '<button style="float:right;" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''+rowKey+'\');">Delete Item</button>';
        html += '<input type="hidden" name="'+rowId+'" value="'+counter+'" />';
        html += '</p>';

        html += '<div><textarea id="'+rowKey+'_contents" name="'+rowKey+'_contents" style="width:100%;height:100px;"></textarea></div>';

        html += '</div>'; // Wrapper

        jQuery("#addMoreDrcInstructionRows").before(html);

        //tinymce.execCommand( 'mceAddEditor', true, rowKey+'_contents' );
    },

    /*  SLUG HANDLING */
    createSlug:function(slugElemId, sourceElemId, slugClassName){
        var newSlug = jQuery('#'+sourceElemId).val();
        jQuery('#slug_'+slugClassName+'_preview').val(newSlug);
        setTimeout(DSCF_DTG_Admin.validateSlug(slugElemId, slugClassName), 100);
    },

    validateSlug:function(slugElemId, slugClassName){
        //Get the proposed value
        var newSlug = jQuery('#slug_'+slugClassName+'_preview').val();
        jQuery('#slug_'+slugClassName+'_previewText').html('<img src="'+DSCF_DTG_vars.url+'/images/loading2.gif" style="vertical-align:top; width:16px;" />');

        var objectId = jQuery('#id').val();

        var queryParams = {
            class_name: slugClassName,
            slug_text: newSlug,
            elem_id: slugElemId,
            action: 'WPDEVHUB_CONST_DTG_SLUG-validate-slug',
            object_id: objectId
        };

        //console.log("URL: "+DSCF_DTG_vars.url_ajax);
        //console.dir(queryParams);

        jQuery.post(DSCF_DTG_vars.url_ajax, queryParams, function( response ){
            //console.dir(response);
            jQuery('#'+response.elemId).val(response.slug);
            jQuery('#slug_'+response.class_name+'_preview').val(response.slug);
            jQuery('#slug_'+response.class_name+'_previewText').html('<img src="'+DSCF_DTG_vars.url+'/images/accept.png" style="width:16px;" />');
            setTimeout(function(){
                jQuery('#slug_'+response.class_name+'_previewText').html('');
            }, 4000);
        });

    },
    hexFromRGB:function(r, g, b) {
        var hex = [
            r.toString( 16 ),
            g.toString( 16 ),
            b.toString( 16 )
        ];
        jQuery.each( hex, function( nr, val ) {
            if ( val.length === 1 ) {
                hex[ nr ] = "0" + val;
            }
        });
        return hex.join( "" ).toUpperCase();
    },
    hexToRgb:function(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    },
    refreshSwatch:function(elemId) {
        console.log("refreshSwatch: elemId["+elemId+"]");
        try{
            var red = jQuery( "#"+elemId+"_red" ).slider( "value" );
            var green = jQuery( "#"+elemId+"_green" ).slider( "value" );
            var blue = jQuery( "#"+elemId+"_blue" ).slider( "value" );
            var hex = DSCF_DTG_Admin.hexFromRGB( red, green, blue );
            jQuery( "#"+elemId+"_swatch" ).css( "background-color", "#" + hex );
            jQuery( "#"+elemId+"_swatch_value").attr( "value", hex );
        }catch(e){
            console.log("refreshSwatch: inside catch statement");
        }
    },
    inputSwatchValue:function(elemId){
        console.log("inputSwatchValue elemId["+elemId+"]");
        var hex = jQuery( "#"+elemId+"_swatch_value").attr( "value" );
        hex = hex.replace("#", "");
        jQuery( "#"+elemId+"_swatch_value").attr( "value", hex );
        var result = DSCF_DTG_Admin.hexToRgb(hex);
        if(result != undefined && result != null){
            red = result.r;
            green = result.g;
            blue = result.b;
            jQuery( "#"+elemId+"_red" ).slider( "value", red );
            jQuery( "#"+elemId+"_green" ).slider( "value", green );
            jQuery( "#"+elemId+"_blue" ).slider( "value", blue );
        }
    }

};

var DSCF_DTG_UserMessages = {
    counter:0,
    load: function(){
        var html = jQuery("#dtg-user-messages-tmp").html();
        if(html != undefined){
            jQuery("#dtg-wrapper").prepend(html);
            jQuery("#dtg-user-messages-tmp").remove();
            setTimeout(function(){
                jQuery(".dtg-user-message").fadeOut();
            }, 5000);
        }
    },
    remove: function(id){
        console.log("Removing Message with ID: "+id);
        jQuery('#dimbal_user_message_'+id).fadeOut();
    }
};


var DSCF_DTG_ChartObject = function(){
    var elemId = null;
    var type = null;      // 1 for pie chart, 2 for bar chart, 3 for table, 4 annotation, 5 column
    var options = [];
    var data = [];
};

var DSCF_DTG_Charts = {

    // Google Charts
    isGoogleLoaded:false,
    isGoogleVisLoaded:false,

    // Charts that need to be drawn
    chartsToDo:[],

    loadGoogle:function() {
        if(DSCF_DTG_Charts.isGoogleLoaded){
            DSCF_DTG_Charts.loadGoogleVisualization();
        }else{
            DSCF_DTG_Charts.loadJsFile("https://www.google.com/jsapi?callback=DSCF_DTG_Charts.loadGoogleVisualization");
        }
    },

    loadGoogleVisualization:function() {
        //Validate google object is loaded
        if(google == undefined){
            DSCF_DTG_Charts.loadGoogle();
            return; //Do the main loader
        }else{
            DSCF_DTG_Charts.isGoogleLoaded = true;
        }

        //Load the visualizer
        if(DSCF_DTG_Charts.isGoogleVisLoaded){
            DSCF_DTG_Charts.drawGoogleCharts();
        }else{
            google.load("visualization", "1", {'packages':['corechart','table','annotationchart'], "callback" : DSCF_DTG_Charts.drawGoogleCharts});
        }
    },

    drawGoogleCharts:function(){

        //Validate that google and google.visualization is loaded
        if(DSCF_DTG_Charts.isGoogleLoaded){
            if(google.visualization == undefined){
                DSCF_DTG_Charts.loadGoogleVisualization();
                return; //Do the visualization loader
            }else{
                DSCF_DTG_Charts.isGoogleVisLoaded = true;
            }
        }else{
            DSCF_DTG_Charts.loadGoogle();
            return;	//Start over at the google loader and exit this function
        }

        console.log("Charts to Do");
        console.dir(DSCF_DTG_Charts.chartsToDo);

        // Loop through any elements added to our to-do array...
        while(DSCF_DTG_Charts.chartsToDo.length > 0){
            var dimbalChart = DSCF_DTG_Charts.chartsToDo[0];

            console.log("Drawing Google Chart:");
            console.dir(dimbalChart);

            if(dimbalChart.elemId != undefined){
                var elemId = dimbalChart.elemId;
                var chartElement = document.getElementById(elemId);
                if(chartElement != undefined){

                    // Load the data array
                    var dataArray = [];
                    if(dimbalChart.data != undefined && dimbalChart.data.length > 1){
                        dataArray = dimbalChart.data;
                    }else{
                        // Add an error message on the Chart
                        jQuery("#"+elemId).html("<h3 style='text-align: center;'>No Data to Display</h3>");

                        // Remove the entry from the array
                        DSCF_DTG_Charts.removeArrayEntry(DSCF_DTG_Charts.chartsToDo, 0);

                        continue;   // Can't have missing data or data with just one row in it... Skip this chart
                    }

                    // Load the Options
                    var options = [];
                    if(dimbalChart.options != undefined){
                        options = dimbalChart.options;
                    }

                    // Format for Google
                    var data = google.visualization.arrayToDataTable(dataArray);


                    /*  Assignments vary depending on Chart Type - so skipping for now
                     // Assign random charting colors
                     if(options.colors == undefined){
                     options.colors=[];
                     }
                     for(var i = 0;i < data.getNumberOfRows();i++){      // getNumberOfRows can only be called once data is Google Formatted
                     var randomColor = DSCF_DTG_Charts.getRandomColor();
                     console.log("Random Color: "+randomColor);
                     options.colors.push(randomColor);
                     }
                     */

                    // Setup the Chart Visualization Object
                    var chart = null;
                    if(dimbalChart.type == 1 || dimbalChart.type == undefined){
                        chart = new google.visualization.PieChart(chartElement);
                    }else if(dimbalChart.type == 2){
                        chart = new google.visualization.BarChart(chartElement);
                    }else if(dimbalChart.type == 3){
                        chart = new google.visualization.Table(chartElement);
                    }else if(dimbalChart.type == 4){
                        chart = new google.visualization.AnnotationChart(chartElement);
                    }else if(dimbalChart.type == 5){
                        chart = new google.visualization.ColumnChart(chartElement);
                    }else if(dimbalChart.type == 6){
                        chart = new google.visualization.LineChart(chartElement);
                    }

                    console.log("Options");
                    console.dir(options);

                    // Draw the chart
                    if(chart != null){
                        chart.draw(data, options);
                    }

                }
            }else{
                // Bad Element record in the chartsToDo list
            }

            // Remove the entry from the array
            DSCF_DTG_Charts.removeArrayEntry(DSCF_DTG_Charts.chartsToDo, 0);

        }
    },

    // Create an additional JS file and loads it into the DOM
    loadJsFile:function(filename){
        //create a script element and set it's type and async attributes
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = filename;
        //add the script element to the DOM
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(script, s);
    },

    removeArrayEntry:function(array, from, to){
        var rest = array.slice((to || from) + 1 || array.length);
        array.length = from < 0 ? array.length + from : from;
        return array.push.apply(array, rest);
    },

    getRandomColor:function(){
        var letters = '0123456789ABCDEF'.split('');
        var color = '#';
        for (var i = 0; i < 6; i++ ) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
};


