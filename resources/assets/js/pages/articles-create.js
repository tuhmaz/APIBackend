import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';

// Initialize Tagify on #keywords when DOM is ready
window.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('#keywords');
  if (!input) return;

  // If Tagify already initialized (via turbo/nav), skip
  if (input._tagify) return;

  new Tagify(input, {
    delimiters: ",", // comma
    dropdown: {
      enabled: 0 // show suggestions on input focus if you later provide a whitelist
    },
    originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(',')
  });
});
