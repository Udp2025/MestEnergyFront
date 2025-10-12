export function getAppContext() {
  return window.App ?? {};
}

export function isAuthenticated() {
  return Boolean(getAppContext().isAuthenticated);
}

export function canViewAllSites() {
  return Boolean(getAppContext().abilities?.canViewAllSites);
}

export function currentUserSiteId() {
  return getAppContext().user?.site_id ?? null;
}

export function enforceSiteFilter(filterMap = {}) {
  const siteId = currentUserSiteId();
  if (!isAuthenticated() || canViewAllSites() || !siteId) {
      return filterMap;
  }

  const normalized = String(siteId).replace(/^=/, "");
  return {
    ...filterMap,
    site_id: `=${normalized}`,
  };
}

export function ensureAuthenticatedOrRedirect() {
  if (!isAuthenticated()) {
    window.location.href = "/";
  }
}
