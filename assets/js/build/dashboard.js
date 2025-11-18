/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!************************************!*\
  !*** ./assets/js/src/dashboard.js ***!
  \************************************/
(function () {
  // Dashboard functionality
  document.addEventListener('DOMContentLoaded', function () {
    // Handle accordion tabs
    const accordionTabs = document.querySelectorAll('.iawmlf_dashboard-accordion-tab');
    const accordionContents = document.querySelectorAll('.iawmlf_dashboard-accordion-content');
    // Set ARIA roles
    const accordionNav = document.querySelector('.iawmlf_dashboard-accordion-nav');
    if (accordionNav) accordionNav.setAttribute('role', 'tablist');
    accordionTabs.forEach(function (t) {
      t.setAttribute('role', 'tab');
      t.setAttribute('aria-selected', t.classList.contains('iawmlf_dashboard-accordion-tab--active') ? 'true' : 'false');
    });
    accordionContents.forEach(function (panel) {
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-hidden', panel.classList.contains('iawmlf_dashboard-accordion-content--active') ? 'false' : 'true');
    });
    accordionTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        const targetTab = this.getAttribute('data-tab');

        // Remove active class from all tabs
        accordionTabs.forEach(function (t) {
          t.classList.remove('iawmlf_dashboard-accordion-tab--active');
        });

        // Hide all content panels
        accordionContents.forEach(function (content) {
          content.classList.remove('iawmlf_dashboard-accordion-content--active');
        });

        // Activate clicked tab
        this.classList.add('iawmlf_dashboard-accordion-tab--active');
        this.setAttribute('aria-selected', 'true');

        // Show corresponding content
        const targetContent = document.getElementById(targetTab);
        if (targetContent) {
          targetContent.classList.add('iawmlf_dashboard-accordion-content--active');
          targetContent.setAttribute('aria-hidden', 'false');
        }
        // Update other tabs/panels aria state
        accordionTabs.forEach(function (t) {
          if (t !== this) t.setAttribute('aria-selected', 'false');
        }, this);
        accordionContents.forEach(function (content) {
          if (content !== targetContent) content.setAttribute('aria-hidden', 'true');
        });
      });
    });

    // Handle link check item show/hide functionality
    const linkCheckItems = document.querySelectorAll('.iawmlf_dashboard-link-check-item');
    linkCheckItems.forEach(function (item) {
      const titleLink = item.querySelector('.iawmlf_dashboard-link-check-title');
      const posts = item.querySelector('.iawmlf_dashboard-link-check-posts');
      if (titleLink && posts) {
        // Initially hide the posts section
        posts.style.display = 'none';

        // Style the title link to indicate it's expandable
        titleLink.style.position = 'relative';
        titleLink.style.paddingRight = '20px';

        // Add arrow indicator
        const arrow = document.createElement('span');
        arrow.className = 'iawmlf_dashboard-link-expand-arrow';
        arrow.textContent = '▼';
        arrow.style.position = 'absolute';
        arrow.style.right = '0';
        arrow.style.top = '50%';
        arrow.style.transform = 'translateY(-50%)';
        arrow.style.fontSize = '10px';
        arrow.style.opacity = '0.6';
        arrow.style.transition = 'transform 0.2s ease';
        arrow.style.pointerEvents = 'none';
        titleLink.appendChild(arrow);

        // Add click handler to prevent default link behavior and toggle
        titleLink.addEventListener('click', function (e) {
          e.preventDefault();
          const isVisible = posts.style.display !== 'none';
          if (isVisible) {
            posts.style.display = 'none';
            arrow.style.transform = 'translateY(-50%) rotate(0deg)';
            item.classList.remove('expanded');
          } else {
            posts.style.display = 'block';
            arrow.style.transform = 'translateY(-50%) rotate(180deg)';
            item.classList.add('expanded');
          }
        });

        // Add hover effect
        titleLink.addEventListener('mouseenter', function () {
          arrow.style.opacity = '1';
        });
        titleLink.addEventListener('mouseleave', function () {
          arrow.style.opacity = '0.6';
        });
      }
    });
  });
})();
/******/ })()
;
//# sourceMappingURL=dashboard.js.map