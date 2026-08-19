function loadStoredData(key, defaultValue = null) {
  let saved = localStorage.getItem(key);
  if (saved === null) {
    return defaultValue;
  }
  return JSON.parse(saved);
}

function saveStoredData(key, value) {
  let newValue = JSON.stringify(value);
  localStorage.setItem(key, newValue);
}