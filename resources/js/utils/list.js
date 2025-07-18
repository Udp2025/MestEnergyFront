// resources/js/utils/plot/list.js
/**
 * Populate a <select> with <option> elements.
 * Accepts:
 *   • rows =  [ {...}, {...} ]                ← plain array
 *   • rows = { data:[ {...}, {...} ] }        ← API wrapper
 *
 * idKey   – property that holds the value
 * nameKey – property that holds the visible label
 */
export function fillSelect(select, rows, idKey, nameKey) {
  /* 1 ▸ normalise the payload */
  const list = Array.isArray(rows)
    ? rows
    : Array.isArray(rows?.data)
    ? rows.data
    : [];

  /* 2 ▸ build <option>s */
  select.innerHTML = list
    .map((r) => `<option value="${r[idKey]}">${r[nameKey]}</option>`)
    .join("");

  /* 3 ▸ select the first entry (if any) so .value is never empty */
  if (select.options.length) select.selectedIndex = 0;
}
