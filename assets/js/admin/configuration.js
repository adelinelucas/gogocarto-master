// CONFIGURATION ADMIN, disable the whole feature box according to checkbox "feature active"
document.addEventListener('DOMContentLoaded', function() {
    checkCollaborativeVoteActivated();
    $('.collaborative-feature .sonata-ba-field.sonata-ba-field-inline-natural > .form-group:first-child .icheckbox_square-blue .iCheck-helper').click(checkCollaborativeVoteActivated);

    $('.gogo-feature').each(function() {
        checkGoGoFeatureActivated(this);
    });
    $('.gogo-feature .sonata-ba-field.sonata-ba-field-inline-natural > .form-group:first-child .icheckbox_square-blue .iCheck-helper').click(function() {
        var that = this;
        setTimeout(function() { checkGoGoFeatureActivated($(that).closest('.gogo-feature'));  }, 10);
    });
});

function checkCollaborativeVoteActivated() {
    var collabActive = $('.collaborative-feature .sonata-ba-field.sonata-ba-field-inline-natural > .form-group:first-child .icheckbox_square-blue').hasClass('checked');
    var opacity = collabActive ? '1' : '0.4';
    $('.collaborative-moderation-box').css('opacity', opacity);
}

function checkGoGoFeatureActivated(object) {
    var featureActive = $(object).find('.sonata-ba-field.sonata-ba-field-inline-natural > .form-group:first-child .icheckbox_square-blue').hasClass('checked');
    var opacity = featureActive ? '1' : '0.5';
    $(object).css('opacity', opacity);
}