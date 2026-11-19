# Welcome to your Expo app 👋

This is an [Expo](https://expo.dev) project created with [`create-expo-app`](https://www.npmjs.com/package/create-expo-app).

## Get started

1. Install dependencies

   ```bash
   npm install
   ```

2. Start the app

   ```bash
   npx expo start
   ```

In the output, you'll find options to open the app in a

- [development build](https://docs.expo.dev/develop/development-builds/introduction/)
- [Android emulator](https://docs.expo.dev/workflow/android-studio-emulator/)
- [iOS simulator](https://docs.expo.dev/workflow/ios-simulator/)
- [Expo Go](https://expo.dev/go), a limited sandbox for trying out app development with Expo

## Run on a phone over Wi-Fi

The mobile app calls the Laravel API through a development-only LAN proxy. Keep all three
processes running while testing:

1. In a terminal at the repository root, start Laravel:

   ```powershell
   Set-Location .\backend
   php artisan serve --host=127.0.0.1 --port=8000
   ```

2. In a second terminal at the repository root, start the API proxy:

   ```powershell
   Set-Location .\backend
   node scripts\lan-api-proxy.cjs
   ```

3. In a third terminal, start Expo in LAN mode and scan its QR code with Expo Go:

   ```powershell
   Set-Location .\mobile-app
   npx expo start --lan
   ```

The phone and computer must be on the same Wi-Fi. If Windows Firewall prompts for Node.js,
allow access on private networks. If the phone cannot reach the API, open
`http://<computer-Wi-Fi-IP>:8080/api/login` in the phone's browser; an HTTP 405 response is
expected and confirms the API is reachable. The `EXPO_PUBLIC_API_HOST` environment variable
can override the detected computer address if needed. This proxy is for local development
only; do not expose it to the public internet.

You can start developing by editing the files inside the **app** directory. This project uses [file-based routing](https://docs.expo.dev/router/introduction).

## Get a fresh project

When you're ready, run:

```bash
npm run reset-project
```

This command will move the starter code to the **app-example** directory and create a blank **app** directory where you can start developing.

### Other setup steps

- To set up ESLint for linting, run `npx expo lint`, or follow our guide on ["Using ESLint and Prettier"](https://docs.expo.dev/guides/using-eslint/)
- If you'd like to set up unit testing, follow our guide on ["Unit Testing with Jest"](https://docs.expo.dev/develop/unit-testing/)
- Learn more about the TypeScript setup in this template in our guide on ["Using TypeScript"](https://docs.expo.dev/guides/typescript/)

## Learn more

To learn more about developing your project with Expo, look at the following resources:

- [Expo documentation](https://docs.expo.dev/): Learn fundamentals, or go into advanced topics with our [guides](https://docs.expo.dev/guides).
- [Learn Expo tutorial](https://docs.expo.dev/tutorial/introduction/): Follow a step-by-step tutorial where you'll create a project that runs on Android, iOS, and the web.

## Join the community

Join our community of developers creating universal apps.

- [Expo on GitHub](https://github.com/expo/expo): View our open source platform and contribute.
- [Discord community](https://chat.expo.dev): Chat with Expo users and ask questions.
