/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

class FraLongPooling { // USELESS

    constructor(url) {
        this.url = url;
    }

    execute(timestamp) {
        const self = this;
        const param = {'timestamp': timestamp};
        console.log("test " + window.counter++);
        $.ajax(
            {
                type: 'GET',
                url: this.url,
                data: param,
                success: function (data) {
                    console.log(data);
                    setTimeout(function () {
                        self.execute(data.timestamp);
                    }, 5000);
                }
            }
        );
    }
}