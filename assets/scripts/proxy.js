const domains = {
  'adam.curry.com': 'adam',
  'fcassets.curry.com.s3.wasabisys.com': 'fcassets',
};

export function getProxyUrl(url) {
  const isChrome = /chrome/.test(window.navigator.userAgent.toLowerCase());

  // Proxy is only required for Chrome-based browsers
  if (!isChrome) {
    return url;
  }

  if (url.substring(0, 8) === 'https://') {
    return url;
  }

  for (const domain in domains) {
    const prefix = `http://${domain}`;

    if (url.startsWith(prefix)) {
      return `/proxy/${domains[domain]}/${url.substring(prefix.length + 1)}`;
    }
  }

  return url;
}
