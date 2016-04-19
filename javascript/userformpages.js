(function($) {
    $.entwine('ss', function($) {
        function getPropertyForEditableField(element, fieldName, property)
        {
            var fieldItems = $(element).data('field-items');
            if (!fieldItems) {
                console.log('Error: Missing "data-field-items" on provided element.');
                return;
            }
            if (typeof fieldItems[fieldName] === 'undefined') {
                console.log('Error: Unable to find field data by name "'+fieldName+'".');
                return;
            }
            var fieldData = fieldItems[fieldName];
            if (typeof fieldData[property] === 'undefined') {
                console.log('Error: Unable to find "'+property+'" property in element "data-field-items".');
                return;
            }
            return fieldData[property];
        }

        $('textarea.js-field-template').entwine({
            hintText: '',
            onmouseup:function(e) {
                this._super();
                //
                // Get selected text from element
                //
                var element = this[0];
                var selectedText = '';
                // IE version
                if (document.selection != undefined)
                {
                    element.focus();
                    var sel = document.selection.createRange();
                    selectedText = sel.text;
                }
                // Mozilla version
                else if (element.selectionStart != undefined)
                {
                    var startPos = element.selectionStart;
                    var endPos = element.selectionEnd;
                    selectedText = element.value.substring(startPos, endPos);
                }
                //
                // If update hint text
                //
                var newHintText = '(none)';
                if (selectedText)
                {
                    selectedText = selectedText.trim();
                    var fieldItems = $(element).data('field-items');
                    var fieldData = null;
                    if (typeof fieldItems[selectedText] !== 'undefined') {
                        fieldData = fieldItems[selectedText];
                    }
                    if (fieldData && typeof fieldData.Name !== 'undefined')
                    {
                        newHintText = fieldData.Title;
                    }
                }
                if (this.hintText !== newHintText)
                {
                    this.hintText = newHintText;
                    var $hintElement = $('.js-field-template-hint').filter('[data-field-name='+$(element).attr('name')+']');
                    if (!$hintElement.length) {
                        console.log('Error: Unable to find "'+$hintElement.selector+'".');
                    }
                    $hintElement.html(this.hintText);
                }
            }
        });

        $('.js-field-template-add').entwine({
            onclick:function(e) {
                this._super();
                var fieldName = $(this).data('field-name');
                var $markupField = $('.js-field-template').not('div').filter('[name='+fieldName+']');
                if (!$markupField.length) {
                    console.log('Error: Unable to find markup field "'+fieldName+'"');
                    return;
                }
                var $selectField = $('select.js-field-template-select').filter('[data-field-name='+fieldName+']');
                if (!$selectField.length) {
                    console.log('Error: Unable to find select field "'+fieldName+'"');
                    return;
                }
                var name = $selectField.val();
                
                var currVal = $markupField.val();
                if (currVal) {
                    currVal += "\n";
                }
                $markupField.val(currVal + getPropertyForEditableField($markupField, name, 'Markup'));
            }
        });
    });
}(jQuery));