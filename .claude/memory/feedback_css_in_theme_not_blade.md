---
name: feedback_css_in_theme_not_blade
description: "Put CSS in theme.css, never in inline <style> blocks in blade views."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: fa7b733f-736c-455a-a230-ae16dc9ec6f3
---

Never add styling via inline `<style>` blocks in Blade views (including vendor overrides). All CSS goes in `resources/css/filament/app/theme.css`.

**Why:** The user pulled an inline `<style>` block out of a customized vendor sidebar blade and told me to move it to theme.css. They want one home for styles, not CSS scattered in markup.

**How to apply:** When a blade customization needs new CSS, add a class in the blade and define the rule in `theme.css`. Only inline `style="..."` attributes for truly one-off/dynamic values are acceptable; standalone `<style>` blocks are not. Requires a theme rebuild (`npm run dev` / `npm run build`) to take effect. Relates to [[feedback_no_code_comments]] and the rule that vendor-file edits get wrapped in `CUSTOM` comments.

**CSS is an exception to the no-comments rule:** group `theme.css` rules into sections with delimiter comment banners (a `/* --- */` rule line, a title line, a closing `/* --- */`) for readability. The blanket "no comments" ([[feedback_no_code_comments]]) applies to PHP/logic, not to organizing a stylesheet.
