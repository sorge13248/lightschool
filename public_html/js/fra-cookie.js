class FraCookie {
    /*
     * @param expires: must be expressed GMT string
     */
    static set(name, value, expires = null, path = null, domain = null) {
        if (expires === null) {
            expires = "Fri, 31 Dec 9999 23:59:59 GMT";
        }

        if (path === null) {
            path = "/";
        }
        if (domain === null) {
            let windowDomain = window.location.host;
            windowDomain = windowDomain.replace("www", "");
            domain = ";domain=" + windowDomain;
        }

        document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + ";expires=" + expires + ";path=" + path + ";domain=" + domain;
    }

    static get(name) {
        const v = document.cookie.match('(^|;) ?' + encodeURIComponent(name) + '=([^;]*)(;|$)');
        return v ? decodeURIComponent(v[2]) : null;
    }

    static delete(name) {
        this.set(encodeURIComponent(name), '', -1);
    }
}