async function loadTemplate(templatePath, containerId) {
  const basePath = '/portal-repo-og';
  const response = await fetch(templatePath);
  let html = await response.text();

  html = html
    .replace(/src="(?!https?:|\/)([^"]+)"/g, `src="${basePath}/$1"`)
    .replace(/href="(?!https?:|\/)([^"]+)"/g, `href="${basePath}/$1"`);

  document.getElementById(containerId).innerHTML = html;
}
