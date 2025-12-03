document.addEventListener("DOMContentLoaded", () => {
    const cnpjInput = document.getElementById("cnpjUnidade");
    const telefoneInput = document.getElementById("telefoneUnidade");

    if (cnpjInput) {
        cnpjInput.addEventListener("input", function (e) {
            let v = e.target.value.replace(/\D/g, "");
            if (v.length <= 14) {
                v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                v = v.replace(/(\d{4})(\d)/, "$1-$2");
            }
            e.target.value = v;
        });
    }

    if (telefoneInput) {
        telefoneInput.addEventListener("input", function (e) {
            let v = e.target.value.replace(/\D/g, "");
            let formatado = "";
            if (v.length > 0) formatado = "(";
            if (v.length >= 2) formatado += v.substring(0, 2) + ") ";
            if (v.length >= 6) formatado += v.substring(2, 6) + "-" + v.substring(6, 10);
            else formatado += v.substring(2);
            e.target.value = formatado;
        });
    }
});