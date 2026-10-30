import {
    test,
    expect,
    configureCsp,
    openHomepage,
    getEnforcedScriptSrc,
    getFixtureScript,
    expectScriptExecuted,
    fixtureScripts,
    sha256,
} from './csp';

/**
 * Shared test cases for Luma and Hyva, so both themes are covered with the very same assertions
 */
export function defineCspUtilitiesTests(theme: string) {
    test('adds a CSP nonce to inline scripts in nonce mode', async function ({page}) {
        await configureCsp(page, 'nonce', theme);
        const response = await openHomepage(page);
        const scriptSrc = await getEnforcedScriptSrc(response);

        for (const datasetKey of Object.values(fixtureScripts)) {
            const script = await getFixtureScript(page, datasetKey);
            expect(script.nonce, `Nonce of ${datasetKey}`).not.toBe('');
            expect(scriptSrc).toContain(`'nonce-${script.nonce}'`);
            await expectScriptExecuted(page, datasetKey);
        }
    });

    test('adds a CSP hash of inline scripts in hash mode', async function ({page}) {
        await configureCsp(page, 'hash', theme);
        const response = await openHomepage(page);
        const scriptSrc = await getEnforcedScriptSrc(response);

        for (const datasetKey of Object.values(fixtureScripts)) {
            const script = await getFixtureScript(page, datasetKey);
            expect(script.nonce, `Nonce of ${datasetKey}`).toBe('');
            expect(scriptSrc, `Hash of ${datasetKey}`).toContain(`'sha256-${sha256(script.content)}'`);
            await expectScriptExecuted(page, datasetKey);
        }
    });
}
