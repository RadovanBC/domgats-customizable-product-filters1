/* (function ($) {
	console.log("script loaded");
	elementorFrontend.hooks.addAction("elementor/frontend/element_ready/loop-grid.default", function () {
		// This function updates the visual "in-cart" style. (No changes here)

		console.log("loop-grid loaded");
		function updateInCartStyles() {
			console.log("updateincart initiated");
			$(".ams-product-count").each(function () {
				const quantityElement = $(this);
				const quantity = parseInt(quantityElement.text());
				const productCard = quantityElement.closest(".e-loop-item");

				if (!isNaN(quantity) && quantity > 0) {
					productCard.addClass("in-cart");
					console.log("in-cart added");
				} else {
					productCard.removeClass("in-cart");
				}
			});
			console.log("updateincart finished");
		}

		console.log("updateInCartStyles before");

		updateInCartStyles();

		console.log("updateInCartStyles after");
	});
})(jQuery);
 */