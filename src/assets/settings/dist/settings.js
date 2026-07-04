/* Site Tint settings page behavior: preset galleries, a live mockup
 * preview, and per-site revert-to-native. One instance per `.st-site`
 * section — each site's fields, mockup, and swatch strip are wholly
 * contained within it, so nothing here needs to know about sibling sites.
 */
(function ($) {
  if (typeof Garnish === 'undefined' || typeof Craft === 'undefined') {
    return;
  }

  const GROUP_KEYS = ['sidebar', 'header', 'content', 'controls'];

  Craft.SiteTintSite = Garnish.Base.extend({
    $container: null,
    $hint: null,

    init: function (container) {
      this.$container = $(container);
      this.$hint = this.$container.find('[data-st-hint]');

      this.addListener(this.$container, 'click', 'onClick');
      this.addListener(this.$container, 'input, change', 'onFieldChange');

      this.refreshPreview();
    },

    onClick: function (ev) {
      const $preset = $(ev.target).closest('[data-st-preset]');
      if ($preset.length) {
        this.applyTheme(JSON.parse($preset.attr('data-st-preset')));
        return;
      }

      const $reset = $(ev.target).closest('[data-st-reset]');
      if ($reset.length) {
        ev.preventDefault();
        this.resetToNative();
      }
    },

    onFieldChange: function (ev) {
      if (!$(ev.target).hasClass('color-input')) {
        return;
      }
      this.refreshPreview();
    },

    /**
     * Fills every field the theme provides and refreshes the preview.
     * Values are staged in the form only — nothing persists until Save.
     */
    applyTheme: function (theme) {
      GROUP_KEYS.forEach((group) => {
        const values = theme[group];
        if (!values) {
          return;
        }
        Object.keys(values).forEach((key) => {
          this.setFieldValue(group, key, values[key]);
        });
      });

      this.refreshPreview();
      this.showHint(Craft.t('site-tint', 'Preset applied — save to keep it.'));
    },

    /**
     * Clears every color field in this site's section so it reverts to
     * native Craft styling once saved.
     */
    resetToNative: function () {
      this.$container.find('.color-input').each((i, el) => {
        this.setInputValue($(el), '');
      });

      this.refreshPreview();
      this.showHint(Craft.t('site-tint', 'Cleared — save to revert to native styling.'));
    },

    setFieldValue: function (group, key, hexValue) {
      const $input = this.$container
        .find(`[data-st-field="${group}.${key}"]`)
        .find('.color-input');
      this.setInputValue($input, String(hexValue).replace(/^#/, ''));
    },

    setInputValue: function ($input, bareHexValue) {
      if (!$input.length) {
        return;
      }

      $input.val(bareHexValue);

      const $preview = $input
        .closest('[data-st-field]')
        .find('.color-preview');
      $preview.css('background-color', bareHexValue ? `#${bareHexValue}` : '');
    },

    /**
     * Reads every field currently in the form and updates the swatch strip
     * and live mockup to match — this is what makes empty fields render as
     * native Craft styling (the mockup's CSS custom properties simply
     * aren't set, so its stylesheet fallbacks take over).
     */
    refreshPreview: function () {
      const theme = this.readTheme();
      const el = this.$container.get(0);

      const setVar = (prop, value) => {
        if (value) {
          el.style.setProperty(prop, value);
        } else {
          el.style.removeProperty(prop);
        }
      };

      setVar('--st-sidebar-bg', theme.sidebar.bg);
      setVar('--st-sidebar-text', theme.sidebar.text);
      setVar('--st-sidebar-active-bg', theme.sidebar.activeBg);
      setVar('--st-header-bg', theme.header.bg);
      setVar('--st-header-text', theme.header.text);
      setVar('--st-content-bg', theme.content.bg);
      setVar('--st-content-pane-bg', theme.content.paneBg);
      setVar('--st-accent', theme.controls.accent);

      const stripColors = [
        theme.sidebar.bg,
        theme.header.bg,
        theme.content.bg,
        theme.controls.accent,
      ];
      this.$container.find('[data-st-strip]').html(
        stripColors
          .map((color) => `<span style="background-color: ${color || 'transparent'}"></span>`)
          .join('')
      );
    },

    /**
     * @return {Object} The theme currently staged in the form fields,
     * shaped like the PHP Settings::GROUPS constant.
     */
    readTheme: function () {
      const theme = { sidebar: {}, header: {}, content: {}, controls: {} };

      this.$container.find('[data-st-field]').each((i, el) => {
        const $field = $(el);
        const [group, key] = $field.attr('data-st-field').split('.');
        const value = $field.find('.color-input').val().trim();
        if (value) {
          theme[group][key] = `#${value.replace(/^#/, '')}`;
        }
      });

      return theme;
    },

    showHint: function (message) {
      if (!this.$hint.length) {
        return;
      }
      this.$hint.text(message).removeClass('hidden');
    },
  });

  const initSections = () => {
    document.querySelectorAll('[data-st-site]').forEach((el) => {
      if (!el.siteTintInstance) {
        el.siteTintInstance = new Craft.SiteTintSite(el);
      }
    });
  };

  if (window.Garnish && Garnish.$doc) {
    Garnish.$doc.ready(initSections);
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSections);
  } else {
    initSections();
  }
})(jQuery);
