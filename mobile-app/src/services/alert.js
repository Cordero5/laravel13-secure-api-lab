import { Alert, Platform } from 'react-native';

// React Native's Alert.alert() is a no-op in the web build, so every message
// this app shows after a form submit would silently disappear in a browser.
// On web we fall back to the native dialog and run the "OK" action ourselves.
export function notify(title, message, buttons) {
  if (Platform.OS === 'web' && typeof window !== 'undefined') {
    const body = message ? `${title}\n\n${message}` : title;
    window.alert(body);
    const ok = (buttons || []).find((b) => b?.onPress) || (buttons || [])[0];
    if (ok?.onPress) ok.onPress();
    return;
  }

  Alert.alert(title, message, buttons);
}
