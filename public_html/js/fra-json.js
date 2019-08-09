class FraJson {
    constructor(url, debug = false) {
        var Httpreq = new XMLHttpRequest();
        Httpreq.open("GET", url, false);
        Httpreq.send(null);

        this.json = JSON.parse(Httpreq.responseText);

        if (debug) console.log(this.json);
    }

    getAll() {
        return this.json;
    }

    get(string) {
        return this.json[string];
    }
}