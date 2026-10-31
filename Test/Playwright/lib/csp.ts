import {test, expect, Page, Response} from '@playwright/test';
import {createHash} from 'crypto';

export const HYVA_THEME = 'Hyva/default-csp';
export const LUMA_THEME = 'Magento/luma';

export type InlineScriptMode = 'nonce' | 'hash';

export const fixtureScripts = {
    plain: 'cspUtilitiesPlain',
    textJavascript: 'cspUtilitiesTextJavascript',
};

/**
 * Rendered without ReplaceInlineScripts, so CSP must block it
 */
export const unprocessedScript = 'cspUtilitiesUnprocessed';

export const magentoInitMarker = 'cspUtilitiesMagentoInit';

const token = () => process.env.TEST_TOKEN;

/**
 * Configure the inline script mode and the theme, with CSP enforced on the storefront
 *
 * Inline scripts are disallowed, because Magento leaves out all nonces and hashes when 'unsafe-inline' is allowed.
 *
 * The Loki_FunctionalTests configure endpoint resets the theme to Magento/luma when `theme` is missing,
 * so the theme is passed with every call.
 */
export async function configureCsp(page: Page, mode: InlineScriptMode, theme: string) {
    const response = await page.request.post('/loki_functional_tests/index/configure?token=' + token(), {
        form: {
            config: JSON.stringify({
                theme,
                config: {
                    'csp_utilities/settings/inline_script_mode': mode,
                    'csp/mode/storefront/report_only': 0,
                    'csp/policies/storefront/scripts/inline': 0,
                },
            }),
        },
    });

    const body = await response.text();
    let data: any;
    try {
        data = JSON.parse(body);
    } catch (error) {
        throw new Error(`Configure endpoint returned no JSON (status ${response.status()}): ${body.slice(0, 500)}`);
    }

    if (data.error) {
        throw new Error('Configure error: ' + data.error);
    }

    const themeErrors: string[] = data.result?.theme?.errors ?? [];
    test.skip(themeErrors.some(error => error.includes('not found')), `Theme "${theme}" is not installed`);
    expect(themeErrors, 'Configure theme errors').toEqual([]);
}

/**
 * Open the homepage, bypassing the full page cache, so the CSP header belongs to this very rendering
 */
export async function openHomepage(page: Page): Promise<Response> {
    const response = await page.goto('/?csp_utilities=' + Date.now());
    expect(response, 'Homepage response').not.toBeNull();
    expect(response!.status()).toBe(200);
    return response!;
}

export async function getEnforcedScriptSrc(response: Response): Promise<string> {
    const headers = await response.headersArray();
    const policies = headers
        .filter(header => header.name.toLowerCase() === 'content-security-policy')
        .map(header => header.value);
    expect(policies.length, 'Enforced Content-Security-Policy header').toBeGreaterThan(0);

    const scriptSrc = policies.join(';')
        .split(';')
        .map(directive => directive.trim())
        .filter(directive => directive.startsWith('script-src '));
    expect(scriptSrc.length, 'script-src directive').toBeGreaterThan(0);

    return scriptSrc.join(' ');
}

/**
 * Return the fixture script that sets the given body dataset key
 */
export async function getFixtureScript(page: Page, datasetKey: string): Promise<{content: string, nonce: string}> {
    const scripts = await page.evaluate(key => Array.from(document.querySelectorAll('script'))
        .filter(script => (script.textContent ?? '').includes(`document.body.dataset.${key} = `))
        .map(script => ({content: script.textContent ?? '', nonce: script.nonce})), datasetKey);
    expect(scripts.length, `Fixture script for ${datasetKey} (is Yireo_CspUtilitiesTestFixture enabled?)`).toBe(1);

    return scripts[0];
}

export async function expectScriptExecuted(page: Page, datasetKey: string) {
    await expect.poll(
        () => page.evaluate(key => document.body.dataset[key], datasetKey),
        `Inline script ${datasetKey} was executed`
    ).toBe('executed');
}

export async function expectScriptNotExecuted(page: Page, datasetKey: string) {
    expect(
        await page.evaluate(key => document.body.dataset[key], datasetKey),
        `Inline script ${datasetKey} was blocked`
    ).toBeUndefined();
}

/**
 * Record CSP violations of the next navigation; for inline scripts, the event target is the blocked script element
 */
export async function collectCspViolations(page: Page) {
    await page.addInitScript(() => {
        const violations: string[] = [];
        (window as any).cspUtilitiesViolations = violations;
        document.addEventListener('securitypolicyviolation', event => {
            if (event.target instanceof HTMLScriptElement) {
                violations.push(event.target.textContent ?? '');
            }
        });
    });
}

export async function getViolatedScripts(page: Page): Promise<string[]> {
    return page.evaluate(() => (window as any).cspUtilitiesViolations ?? []);
}

/**
 * Return the opening tag and content of the x-magento-init fixture script from the server HTML,
 * because Luma removes x-magento-init scripts from the DOM once processed
 */
export async function getMagentoInitScript(response: Response): Promise<{openingTag: string, content: string}> {
    const html = await response.text();
    const matches = Array.from(html.matchAll(/(<script\b[^>]*>)([^<]*)<\/script>/gi))
        .filter(match => match[2].includes(magentoInitMarker));
    expect(matches.length, 'x-magento-init fixture script').toBe(1);

    return {openingTag: matches[0][1], content: matches[0][2]};
}

export function sha256(content: string): string {
    return createHash('sha256').update(content, 'utf8').digest('base64');
}

export {test, expect};
