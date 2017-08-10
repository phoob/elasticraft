/**
 * Elasticraft plugin for Craft CMS
 *
 * ElasticraftUtility Utility JS
 *
 * @author    Peter Holme Obrestad
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 * @link      https://dfo.no
 * @package   Elasticraft
 * @since     1.0.0
 */

var buttons = $("form.elasticraft input[type='button']");
var executing = false;

$(document).ready(function(){
    refreshContent();
    this.$status = $('.utility-status', this.$form);
});

function refreshContent() {
    Craft.postActionRequest('elasticraft/default/ping', function(connectionWorks) {
        if (connectionWorks) {
            $('#connectionNotWorking').addClass('hidden');
            Craft.postActionRequest('elasticraft/default/index-exists', function(indexExists) {
                if (indexExists) {
                    $('#indexDoesNotExist').addClass('hidden');
                    $('#indexUtilities').removeClass('hidden');
                    
                    Craft.postActionRequest('elasticraft/default/index-stats', function(indexStats) {
                        $('#numDocsIndexed').html(indexStats['_all']['primaries']['docs']['count']);
                    });

                } else {
                    $('#indexUtilities').addClass('hidden');
                    $('#indexDoesNotExist').removeClass('hidden');
                }
            });
        } else {
            $('#connectionNotWorking').removeClass('hidden');
        }
    });
}

buttons.on("click", function(ev) {
    ev.preventDefault();

    if (executing) {
        return;
    }
    executing = true;

    var $this = $(this);
    var action = $this.attr('name');
    var spinner = $('#spinner');
    var results = $('#elasticraft-result');

    $this.addClass('active');
    results.html('<div id="spinner" class="spinner"></div>');

    Craft.postActionRequest(action, function(response) {
        $this.removeClass('active');
        spinner.addClass('hidden');
        executing = false;
        results.html( syntaxHighlight(JSON.stringify(response, null, 4)) );
        refreshContent();
    });

});

function syntaxHighlight(json) {
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}


