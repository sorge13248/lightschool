class FraForm {
    constructor(form, loading = null) {
        this.form = form;
        this.loading = loading === null ? "Loading..." : loading;
    }

    lock() {
        const self = this;
        this.form.find("input, select, textarea").each(function () {
            $(this).attr("disabled", "disabled");
        });
        this.form.find("input[type='submit']").each(function () {
            $(this).attr("prev-value", $(this).attr("value"));
            $(this).attr("value", self.loading);
        });
    }

    unlock() {
        this.form.find("input, select, textarea").each(function () {
            $(this).removeAttr("disabled");
        });
        this.form.find("input[type='submit']").each(function () {
            $(this).attr("value", $(this).attr("prev-value"));
            $(this).removeAttr("prev-value");
        });
    }

    getDomElements(elements, type) {
        let returnValues;

        if (type === "string") {
            returnValues = "";
        } else if (type === "array") {
            returnValues = [];
        } else if (type === "associative-array") {
            returnValues = {};
        }

        this.form.find(elements).each(function () {
            if (type === "string") {
                if (returnValues !== "") {
                    returnValues = returnValues + "&";
                }

                let val = $(this).val();
                if (!val) {
                    val = "";
                }

                returnValues = returnValues + $(this).attr("id") + "=" + val;
            } else if (type === "array") {
                if ($(this).attr("type") === "checkbox") {
                    returnValues.push($(this).attr("id"));
                } else {
                    returnValues.push($(this).val());
                }
            } else if (type === "associative-array") {
                returnValues[$(this).attr("id")] = $(this).val();
            }
        });

        return returnValues;
    }

    getInput(type = "string") {
        // this method will not get input of type checkbox
        return this.getDomElements("input:not([type='checkbox'])", type);
    }

    getRadio(type = "string") {
        return this.getDomElements("input[type='radio']:checked", type);
    }

    getSelect(type = "string") {
        return this.getDomElements("select", type);
    }

    getAll(type = "string") {
        // this method will not get input of type checkbox
        return this.getDomElements("input:not([type='checkbox']):not([type='radio']), input[type='radio']:checked, input[type='checkbox']:checked, select, textarea", type);
    }

    getFields(type = "string") {
        const input = this.getInput(type);
        const select = this.getSelect(type);

        if (input && type === "string") {
            return input + "&" + select;
        } else if (input && type === "array") {
            return input.concat(select);
        } else if (input && type === "associative-array") {
            return Object.assign({}, input, select);
        }

        if (!input && select) {
            return select;
        }
    }
}