import {
    test,
    expect,
    configureCsp,
    openHomepage,
    openUncacheablePage,
    isPubliclyCacheable,
    getEnforcedScriptSrc,
    getFixtureScript,
    getMagentoInitScript,
    expectScriptExecuted,
    expectScriptNotExecuted,
    collectCspViolations,
    getViolatedScripts,
    fixtureScripts,
    bodyMarkup,
    unprocessedScript,
    unmarkedScripts,
    sha256,
    InlineScriptMode,
} from './csp';

const modes: InlineScriptMode[] = ['nonce', 'hash'];

/**
 * Shared test cases for Luma and Hyva, so both themes are covered with the very same assertions
 */
export function defineCspUtilitiesTests(theme: string) {
    test('adds a CSP nonce to marked inline scripts in nonce mode', async function ({page}) {
        await configureCsp(page, 'nonce', theme);
        const response = await openUncacheablePage(page);
        const scriptSrc = await getEnforcedScriptSrc(response);

        for (const datasetKey of Object.values(fixtureScripts)) {
            const script = await getFixtureScript(page, datasetKey);
            expect(script.nonce, `Nonce of ${datasetKey}`).not.toBe('');
            expect(script.marker, `Marker of ${datasetKey}`).toBeNull();
            expect(scriptSrc).toContain(`'nonce-${script.nonce}'`);
            await expectScriptExecuted(page, datasetKey);
        }
    });

    test('falls back to CSP hashes on publicly cacheable pages in nonce mode', async function ({page}) {
        await configureCsp(page, 'nonce', theme);
        const response = await openHomepage(page);
        test.skip(false === await isPubliclyCacheable(response), 'The full page cache is disabled');
        const scriptSrc = await getEnforcedScriptSrc(response);

        for (const datasetKey of Object.values(fixtureScripts)) {
            const script = await getFixtureScript(page, datasetKey);
            expect(script.nonce, `Nonce of ${datasetKey}`).toBe('');
            expect(scriptSrc, `Hash of ${datasetKey}`).toContain(`'sha256-${sha256(script.content)}'`);
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
            expect(script.marker, `Marker of ${datasetKey}`).toBeNull();
            expect(scriptSrc, `Hash of ${datasetKey}`).toContain(`'sha256-${sha256(script.content)}'`);
            await expectScriptExecuted(page, datasetKey);
        }
    });

    test('generates a fresh nonce for every request in nonce mode', async function ({page}) {
        await configureCsp(page, 'nonce', theme);

        await openUncacheablePage(page);
        const firstNonce = (await getFixtureScript(page, fixtureScripts.plain)).nonce;
        await openUncacheablePage(page);
        const secondNonce = (await getFixtureScript(page, fixtureScripts.plain)).nonce;

        expect(firstNonce).not.toBe('');
        expect(secondNonce).not.toBe('');
        expect(secondNonce).not.toBe(firstNonce);
    });

    for (const mode of modes) {
        test(`leaves script markup inside an inline script body untouched in ${mode} mode`, async function ({page}) {
            await configureCsp(page, mode, theme);
            await openHomepage(page);

            await expectScriptExecuted(page, fixtureScripts.bodyMarkup);
            expect(await page.evaluate(() => (window as any).cspUtilitiesBodyMarkup)).toBe(bodyMarkup);

            const script = await getFixtureScript(page, fixtureScripts.bodyMarkup);
            expect(script.content).not.toContain('nonce=');
        });

        test(`only blocks unprocessed and unmarked inline scripts in ${mode} mode`, async function ({page}) {
            await collectCspViolations(page);
            await configureCsp(page, mode, theme);
            await openHomepage(page);

            for (const datasetKey of [unprocessedScript, ...Object.values(unmarkedScripts)]) {
                await expect.poll(() => getViolatedScripts(page), `CSP violation of ${datasetKey}`)
                    .toContainEqual(expect.stringContaining(`dataset.${datasetKey} = `));
                await expectScriptNotExecuted(page, datasetKey);
            }

            const violatedScripts = await getViolatedScripts(page);
            for (const datasetKey of Object.values(fixtureScripts)) {
                await expectScriptExecuted(page, datasetKey);
                expect(
                    violatedScripts.filter(script => script.includes(`dataset.${datasetKey} = `)),
                    `CSP violations of ${datasetKey}`
                ).toEqual([]);
            }
        });

        test(`leaves non-JavaScript scripts alone in ${mode} mode`, async function ({page}) {
            await configureCsp(page, mode, theme);
            const response = await openHomepage(page);
            const scriptSrc = await getEnforcedScriptSrc(response);

            const magentoInitScript = await getMagentoInitScript(response);
            expect(magentoInitScript.openingTag).toBe('<script type="text/x-magento-init">');
            expect(scriptSrc).not.toContain(`'sha256-${sha256(magentoInitScript.content)}'`);
        });
    }
}
