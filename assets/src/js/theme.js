import lightGallery from "lightgallery";
import lgThumbnail from "lightgallery/plugins/thumbnail"
import lgZoom from "lightgallery/plugins/zoom"

window.lightGallery = lightGallery;
window.lgThumbnail = lgThumbnail;
window.lgZoom = lgZoom;

window.$ = window.jQuery = require("jquery");

var gallery = document.getElementById("gallery");

if (gallery) {
    lightGallery(gallery, {
        selector: ".swiper-image",
        plugins: [lgZoom, lgThumbnail],
        speed: 500,
        galleryId: 1,
    });
}

if (document.querySelector(".slider-swiper")) {
    var sliderSwiper = new Swiper(".slider-swiper", {
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        autoplay: {
            delay: 5000,
        },
    });
}

if (document.querySelector(".videos-swiper")) {
    var videosSwiper = new Swiper(".videos-swiper", {
    scrollbar: {
        el: ".swiper-scrollbar",
        hide: false,
    },
    slidesPerView: 1,
    spaceBetween: 30,
    slidesOffsetBefore: 12,
    slidesOffsetAfter: 12,
    breakpoints: {
        576: {
            slidesPerView: 2,
        },
        768: {
            slidesPerView: 3,
        },
        992: {
            slidesPerView: 4,
            slidesOffsetBefore: $(".mi-logo").offset().left - 12,
            slidesOffsetAfter: $(".mi-logo").offset().left - 12,
        },
        1200: {
            slidesPerView: 5,
            slidesOffsetBefore: $(".mi-logo").offset().left - 12,
            slidesOffsetAfter: $(".mi-logo").offset().left - 12,
        },
    },
    });
}

if (document.querySelector(".products-swiper")) {
    var productsSwiper = new Swiper(".products-swiper", {
    scrollbar: {
        el: ".swiper-scrollbar",
        hide: false,
    },
    slidesPerView: 1,
    spaceBetween: 24,
    slidesOffsetBefore: 12,
    slidesOffsetAfter: 12,
    breakpoints: {
        576: {
            slidesPerView: 2,
        },
        768: {
            slidesPerView: 3,
        },
        992: {
            slidesPerView: 4,
            slidesOffsetBefore: $(".mi-logo").offset().left - 12,
            slidesOffsetAfter: $(".mi-logo").offset().left - 12,
        },
        1200: {
            slidesPerView: 5,
            slidesOffsetBefore: $(".mi-logo").offset().left - 12,
            slidesOffsetAfter: $(".mi-logo").offset().left - 12,
        },
    },
    });
}

$(document).ready(function () {
    $("#dosage .volume-container").find(".form-control").keyup(function () {
        var shape = $(this).parent().parent().parent().attr("id");
        var volume = $(this).val().replace(",", ".");

        if (volume > 0) {
            if (shape == "kruh") {
                volume = volume * 3.14;
            }

            $(this).parent().parent().siblings().find(".form-control").each(function () {
                var metres = parseFloat($(this).val().replace(",", "."));

                if (metres > 0) {
                    volume = volume * metres;
                }
            });

            if (shape == "oval") {
                volume = volume / 100 * 87.5;
            }

            $(".volume").html(Math.round(volume).toFixed(0));

            updateChlorine();
            updateOxygen();
            updatePh();
            updateHelp();
        }
    });
});

$("#chlorine").keyup(function () {
    var fill = parseFloat($(this).val().replace(",", "."));

    if (fill > 0) {
        updateChlor(true);
    } else {
        updateChlor();
    }
});

$("#oxygen").keyup(function () {
    updateOxygen();
});

$("#ph-input").keyup(function () {
    updatePh();
});

function updateChlorine(fill) {
    var volume = parseFloat($(".volume").html().replace(",", "."));
    var value = parseFloat($("#chlorine").val().replace(",", "."));
    var grams = (value - 0.4) * 0.18 * 100 * volume;
    var amount = Math.round(volume / 2).toFixed(0);
    var amount2 = Math.round(volume / 20).toFixed(0);

    $(".chlorine-shock").find(".shock").html(fill ? "" : Math.round(10 * volume).toFixed(0));
    $(".chlorine-shock").find(".stable").html(fill ? "" : Math.round(5 * volume).toFixed(0));
    $(".chlorine-shock").find(".grams").html(grams <= 0.5 ? Math.round(Math.abs(grams)).toFixed(0) : "nedávkovat");

    $(".blue-arrow").find(".shock").html(Math.round(20 * volume).toFixed(0));
    $(".blue-arrow").find(".stable").html(Math.round(10 * volume).toFixed(0));

    $(".triplex-mini").find(".amount").html(amount > 10 ? "zvolte velkou tabletu" : amount);
    $(".chlorine-mini").find(".amount").html(amount > 10 ? "zvolte velkou tabletu" : amount);
    $(".triplex").find(".amount").html(amount2 < 0.7 ? "zvolte malou tabletu" : amount2);
    $(".quatro").find(".amount").html(amount2 < 0.7 ? "zvolte malou tabletu" : amount2);
    $(".v61").find(".amount").html(amount2 < 0.7 ? "zvolte malou tabletu" : amount2);
}

function updateOxygen() {
    var volume = parseFloat($(".volume").html().replace(",", "."));
    var value = parseFloat($("#oxygen").val().replace(",", "."));
    var amount = Math.round(volume / 20).toFixed(0);
    var amount2 = Math.round(volume / 1).toFixed(0);

    $(".oxi-junior").find(".amount").html(Math.round(volume * 112.5).toFixed(0));

    if (value > 0) {
        $(".oxi-junior").find(".ml").html(Math.round((7 - value) * 18.74 * volume).toFixed(0));
    } else {
        $(".oxi-junior").find(".ml").html("");
    }

    $(".oxi-200").find(".amount").html(amount < 0.6 ? "zvolte malou tabletu" : amount);
    $(".oxi-20").find(".amount").html(amount2 > 10 ? "zvolte velkou tabletu" : amount2);
}

function updatePh() {
    var volume = parseFloat($(".volume").html().replace(",", "."));
    var value = parseFloat($("#ph-input").val().replace(",", "."));
    var plus = Math.round((value - 6.8) / 0.2 * 10 * volume).toFixed(0);
    var minus = Math.round((value - 7.6) / 0.2 * 15 * volume).toFixed(0);

    if (value > 0) {
        $(".ph-plus").find(".plus").html(plus >= 0 ? "nepoužívat" : Math.abs(plus));
        $(".ph-plus").find(".minus").html(minus <= 0 ? "nepoužívat" : Math.abs(minus));
    } else {
        $(".ph-plus").find(".plus").html("");
        $(".ph-plus").find(".minus").html("");
    }
}

function updateHelp() {
    var volume = parseFloat($(".volume").html().replace(",", "."));
    //var value = parseFloat($(".help-products").find(".form-control").val().replace(",", "."));

    $(".flokul").find(".shock").html(Math.round(6 * volume).toFixed(0));
    $(".flokul").find(".stable").html(Math.round(3 * volume).toFixed(0));

    $(".flake-tablets").find(".shock").html(Math.round(volume / 5).toFixed(0));

    $(".alg").find(".shock").html(Math.round(15 * volume).toFixed(0));
    $(".alg").find(".stable").html(Math.round(6 * volume).toFixed(0));

    $(".ca").find(".shock").html(Math.round(50 * volume).toFixed(0));
    $(".ca").find(".stable").html(Math.round(25 * volume).toFixed(0));

    $(".winter").find(".shock").html(Math.round(50 * volume).toFixed(0));

    $(".salt").find(".amount").html(Math.round(3 * volume).toFixed(0));
}
