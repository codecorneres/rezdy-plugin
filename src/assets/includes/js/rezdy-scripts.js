(function () {
    const rezdyScripts = {
        init: function () {
            console.log('Rezdy scripts initialized')

            this.widgetTabSwitcher()
			this.formatLabels()
        },
        widgetTabSwitcher: function () {
            const tabs = document.querySelectorAll('.booking-widget-tabs__item')

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    tabs.forEach(tab => tab.classList.remove('booking-widget-tabs__item--active'))
                    this.classList.add('booking-widget-tabs__item--active')
                    const action = this.getAttribute('data-action')

                    const contents = document.querySelectorAll('.booking-widget-tabs-content')
                    contents.forEach(content => content.classList.remove('booking-widget-tabs-content--active'))
                    contents.forEach(content => {
                        if (content.getAttribute('data-content') === action) {
                            content.classList.add('booking-widget-tabs-content--active')
                        }
                    })
                })
            })
        },
		
		formatLabels: function () {
			console.log('asdfasdfasdf')
			// Function to add extra classes for Child/Infant labels
			function addAgeLabels(el) {
			  let text = el.textContent.trim();

			  if (text === "Child" && !el.classList.contains("booking-is-child")) {
				el.classList.add("booking-is-child");
			  } else if (text === "Infant" && !el.classList.contains("booking-is-infant")) {
				el.classList.add("booking-is-infant");
			  }
			}

			// Run once on any existing labels
			document.querySelectorAll(".priceOptionlabel").forEach(addAgeLabels);

			// Observe for future changes
			const observer = new MutationObserver((mutations) => {
			  mutations.forEach((mutation) => {
				mutation.addedNodes.forEach((node) => {
				  if (node.nodeType === 1) {
					if (node.matches(".priceOptionlabel")) {
					  addAgeLabels(node);
					}
					// Also check inside if multiple labels are added together
					node.querySelectorAll?.(".priceOptionlabel").forEach(addAgeLabels);
				  }
				});
			  });
			});

			observer.observe(document.body, { childList: true, subtree: true });
		}
    }

    document.addEventListener('DOMContentLoaded', () => {
        rezdyScripts.init()
    })
})()