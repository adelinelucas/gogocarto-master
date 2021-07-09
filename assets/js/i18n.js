var gogoFallbackLocale = 'en';

// Use this function anywhere
// handle interpolation :t('js.helo.world', {user: "Seby"})
window.t = function(key, params) {
    key = key.replace(/^js\./, '')
    var result = gogoTrans(gogoLocale + '.' + key, params)
    if (!result) result = gogoTrans(gogoFallbackLocale + '.' + key, params)
    if (!result) result = key
    return result
}

function gogoTrans(key, params) {
    result = gogoI18n // gogoI18n is defined in web/js/javascript-translations.js
    path = key.split('.')
    for(var i = 0; i < path.length; i++) {
        result = result[path[i]] || {}
    }
    if (typeof result == 'string') {
        for(var paramKey in params) {
            result = result.replace('{' + paramKey +'}', params[paramKey])
        }
    }  
    return !result.length ? undefined : result
}