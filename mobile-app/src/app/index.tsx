import { Redirect } from 'expo-router';

// The login screen lives at /login, mirroring /register. This root route only
// exists so an old bookmark or a bare localhost URL still lands there.
export default function Index() {
  return <Redirect href="/login" />;
}
