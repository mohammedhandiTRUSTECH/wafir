function addRule() {
    $.ajax({
        url: "/add-commission-rule",
    }).done(function (resp) {
        $('#commission_rules').append(resp.view);
        let hiddenFieldValue = $("#all_rules").val();
        $("#all_rules").val(hiddenFieldValue + ',' + resp.rand);
    });
}

function removeTarget(randId) {
    let hiddenFieldValue = $("#all_rules").val();
    let newValue = hiddenFieldValue.replace(randId, '');
    $("#all_rules").val(newValue);
    $('#' + randId).remove();
}
