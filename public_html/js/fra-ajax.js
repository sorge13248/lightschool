/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 * @version 1.1
 */

class FraAjax {
    constructor(url, method = "POST", data = "") {
        this.method = method;
        this.url = url;
        this.data = data;
    }

    execute(handleData) {
        $.ajax({
            type: this.method,
            url: this.url,
            data: this.data
        }).done(function (response) {
            handleData(response);
        }).fail(function (response) {
            handleData(response);
        });
    }

    getResult(result, parse = true) {
        if (parse) return JSON.parse(result);
        else return result;
    }

    // Fallback function kept for compatibility issue. Do not use.
    handleResult(result) {
        return this.getResult(result);
    }
}