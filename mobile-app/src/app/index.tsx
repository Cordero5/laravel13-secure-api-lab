import { Redirect } from 'expo-router';

// Keep the tenant entry URL distinct from the backend's /admin/login URL.
export default function Index() {
  return <Redirect href="/tenant/login" />;
}
