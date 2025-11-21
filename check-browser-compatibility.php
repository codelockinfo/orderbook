<?php
/**
 * Browser Compatibility Checker
 * Shows detailed browser compatibility information for push notifications
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browser Compatibility Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .compat-item { padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid; }
        .supported { background: #d4edda; border-color: #28a745; }
        .partial { background: #fff3cd; border-color: #ffc107; }
        .unsupported { background: #f8d7da; border-color: #dc3545; }
        .info { background: #e3f2fd; border-color: #2196F3; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Browser Compatibility Check</h1>
        
        <div id="browserInfo"></div>
        <div id="compatibilityResults"></div>
        
        <h2>📊 Browser Support Matrix</h2>
        <table>
            <thead>
                <tr>
                    <th>Browser/Platform</th>
                    <th>Support</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Chrome Desktop</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>Best support, all features work</td>
                </tr>
                <tr>
                    <td><strong>Chrome Android</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>Best mobile browser support</td>
                </tr>
                <tr>
                    <td><strong>Firefox Desktop</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>Excellent support</td>
                </tr>
                <tr>
                    <td><strong>Firefox Android</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>Good mobile support</td>
                </tr>
                <tr>
                    <td><strong>Edge Desktop</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>Chromium-based, full support</td>
                </tr>
                <tr>
                    <td><strong>Safari Desktop (16+)</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>macOS Ventura+ required</td>
                </tr>
                <tr>
                    <td><strong>Safari iOS (16.4+)</strong></td>
                    <td><span class="badge badge-success">✅ Full</span></td>
                    <td>Must add to Home Screen (PWA)</td>
                </tr>
                <tr>
                    <td><strong>Safari iOS (<16.4)</strong></td>
                    <td><span class="badge badge-danger">❌ None</span></td>
                    <td>Not supported, update iOS required</td>
                </tr>
                <tr>
                    <td><strong>Flutter WebView Android</strong></td>
                    <td><span class="badge badge-warning">⚠️ Usually</span></td>
                    <td>Depends on WebView version</td>
                </tr>
                <tr>
                    <td><strong>Flutter WebView iOS</strong></td>
                    <td><span class="badge badge-warning">⚠️ Limited</span></td>
                    <td>iOS 16.4+ only, requires setup</td>
                </tr>
                <tr>
                    <td><strong>Internet Explorer</strong></td>
                    <td><span class="badge badge-danger">❌ None</span></td>
                    <td>Not supported (use Edge instead)</td>
                </tr>
            </tbody>
        </table>
        
        <h2>📱 iOS Safari Special Requirements</h2>
        <div class="compat-item info">
            <strong>iOS 16.4+ Support:</strong><br>
            Web Push Notifications are supported in iOS Safari 16.4+, but require:
            <ul>
                <li>✅ iOS 16.4 or later</li>
                <li>✅ Website added to Home Screen (PWA)</li>
                <li>✅ HTTPS connection</li>
                <li>✅ Service Worker registered</li>
            </ul>
            <strong>How to add to Home Screen:</strong>
            <ol>
                <li>Open website in Safari</li>
                <li>Tap Share button</li>
                <li>Select "Add to Home Screen"</li>
                <li>Open from Home Screen</li>
                <li>Enable notifications</li>
            </ol>
        </div>
        
        <h2>📱 Flutter WebView Notes</h2>
        <div class="compat-item partial">
            <strong>Android WebView:</strong>
            <ul>
                <li>Usually works if Chrome is installed</li>
                <li>Requires Android 5.0+ (API 21+)</li>
                <li>WebView must be updated</li>
            </ul>
            <strong>iOS WKWebView:</strong>
            <ul>
                <li>Requires iOS 16.4+</li>
                <li>May need special WebView configuration</li>
                <li>Service Worker support varies</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Detect browser
        function detectBrowser() {
            const ua = navigator.userAgent;
            const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
            const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
            const isIOSSafari = isIOS && isSafari;
            const isChrome = /Chrome/.test(ua) && /Google Inc/.test(navigator.vendor);
            const isFirefox = /Firefox/.test(ua);
            const isEdge = /Edge/.test(ua) || /Edg/.test(ua);
            const isAndroid = /Android/.test(ua);
            
            let iosVersion = null;
            if (isIOS) {
                const match = ua.match(/OS (\d+)_(\d+)/);
                if (match) {
                    iosVersion = parseFloat(match[1] + '.' + match[2]);
                }
            }
            
            const isWebView = (isIOS && window.navigator.standalone === false) || 
                             (isAndroid && !/Chrome/.test(ua) && /wv/.test(ua));
            
            return {
                isIOS,
                isSafari,
                isIOSSafari,
                isChrome,
                isFirefox,
                isEdge,
                isAndroid,
                isWebView,
                iosVersion,
                userAgent: ua,
                isStandalone: window.navigator.standalone === true || 
                             window.matchMedia('(display-mode: standalone)').matches
            };
        }
        
        // Check compatibility
        function checkCompatibility() {
            const browser = detectBrowser();
            const hasNotification = 'Notification' in window;
            const hasServiceWorker = 'serviceWorker' in navigator;
            const hasPushManager = 'PushManager' in window;
            const isSupported = hasNotification && hasServiceWorker && hasPushManager;
            
            let browserName = 'Unknown';
            if (browser.isChrome) browserName = 'Chrome';
            else if (browser.isFirefox) browserName = 'Firefox';
            else if (browser.isEdge) browserName = 'Edge';
            else if (browser.isSafari) browserName = 'Safari';
            
            let html = '<div class="compat-item info">';
            html += '<strong>🌐 Your Browser:</strong><br>';
            html += 'Browser: ' + browserName + '<br>';
            html += 'User Agent: <code style="font-size: 11px;">' + browser.userAgent.substring(0, 80) + '...</code><br>';
            if (browser.isIOS) {
                html += 'iOS Version: ' + (browser.iosVersion || 'Unknown') + '<br>';
                html += 'Is Standalone (PWA): ' + (browser.isStandalone ? 'Yes ✅' : 'No ❌') + '<br>';
            }
            if (browser.isWebView) {
                html += '⚠️ Running in WebView<br>';
            }
            html += '</div>';
            
            html += '<div class="compat-item ' + (hasNotification ? 'supported' : 'unsupported') + '">';
            html += '<strong>Notification API:</strong> ' + (hasNotification ? '✅ Supported' : '❌ Not Supported');
            html += '</div>';
            
            html += '<div class="compat-item ' + (hasServiceWorker ? 'supported' : 'unsupported') + '">';
            html += '<strong>Service Worker:</strong> ' + (hasServiceWorker ? '✅ Supported' : '❌ Not Supported');
            html += '</div>';
            
            html += '<div class="compat-item ' + (hasPushManager ? 'supported' : 'unsupported') + '">';
            html += '<strong>Push Manager:</strong> ' + (hasPushManager ? '✅ Supported' : '❌ Not Supported');
            html += '</div>';
            
            html += '<div class="compat-item ' + (isSupported ? 'supported' : 'unsupported') + '">';
            html += '<strong>Overall Support:</strong> ' + (isSupported ? '✅ FULLY SUPPORTED' : '❌ NOT SUPPORTED');
            html += '</div>';
            
            if (browser.isIOSSafari) {
                if (browser.iosVersion && browser.iosVersion < 16.4) {
                    html += '<div class="compat-item unsupported">';
                    html += '<strong>⚠️ iOS Version Too Old</strong><br>';
                    html += 'Web Push Notifications require iOS 16.4 or later. Your version: ' + browser.iosVersion;
                    html += '</div>';
                } else if (browser.iosVersion && browser.iosVersion >= 16.4) {
                    html += '<div class="compat-item ' + (browser.isStandalone ? 'supported' : 'partial') + '">';
                    html += '<strong>📱 iOS Safari 16.4+ Detected</strong><br>';
                    if (browser.isStandalone) {
                        html += '✅ Website is added to Home Screen - Notifications should work!';
                    } else {
                        html += '⚠️ Add this website to Home Screen to enable notifications:<br>';
                        html += '1. Tap Share button<br>';
                        html += '2. Select "Add to Home Screen"<br>';
                        html += '3. Open from Home Screen<br>';
                        html += '4. Enable notifications';
                    }
                    html += '</div>';
                }
            }
            
            if (browser.isWebView) {
                html += '<div class="compat-item partial">';
                html += '<strong>⚠️ WebView Detected</strong><br>';
                html += 'Push notifications may have limited support in WebView. For best results, use a regular browser.';
                html += '</div>';
            }
            
            document.getElementById('browserInfo').innerHTML = html;
        }
        
        // Run on load
        checkCompatibility();
    </script>
</body>
</html>

