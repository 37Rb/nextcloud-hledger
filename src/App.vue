<template>
	<Content :class="{'icon-loading': loading}" app-name="hledger">
		<AppNavigation>
			<template #list>
				<AppNavigationItem title="Balance Sheet" icon="icon-edit" @click="getBalanceSheet" />
				<AppNavigationItem title="Income Statement" icon="icon-clippy" @click="getIncomeStatement" />
				<AppNavigationItem title="Budget" icon="icon-toggle-filelist" @click="getBudget" />
			</template>
			<template #footer>
				<AppNavigationSettings>
					<label for="hledger_folder">HLedger Folder</label>
					<input id="hledger_folder"
						v-model="settings.hledger_folder"
						type="text">
					<label for="journal_file">Journal File</label>
					<input id="journal_file"
						v-model="settings.journal_file"
						type="text">
					<label for="budget_file">Budget File</label>
					<input id="budget_file"
						v-model="settings.budget_file"
						type="text">
					<input type="submit" value="Save" @click="saveSettings">
				</AppNavigationSettings>
			</template>
		</AppNavigation>
		<AppContent>
			<table class="hledger-report">
				<tr v-for="row in report" :key="row.id">
					<td v-for="cell in row" :key="cell.id" :class="{ outline: shouldOutlineRow(row[0]), indent: shouldIndentCell(cell) }">
						<a v-if="isSubAccount(cell)" href="#" @click="getAccountRegister(cell)">{{ cell }}</a>
						<button v-else-if="cell === 'edit'" @click="editTransaction(row)">
							edit
						</button>
						<div v-else>
							{{ truncate(cell, 32) }}
						</div>
					</td>
				</tr>
			</table>
		</AppContent>
	</Content>
</template>
<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationSettings from '@nextcloud/vue/dist/Components/AppNavigationSettings'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
export default {
	name: 'App',
	components: {
		Content,
		AppNavigation,
		AppNavigationItem,
		AppNavigationSettings,
		AppContent,
	},
	data() {
		return OCP.InitialState.loadState('hledger', 'state')
	},
	computed: {},
	methods: {
		apiUrl(x) {
			return generateUrl('/apps/hledger/api/1/' + x)
		},
		truncate(text, stop, clamp) {
			return text.slice(0, stop) + (stop < text.length ? clamp || '...' : '')
		},
		isSubAccount(x) {
			return x.match(/^(assets|liabilities|equity|income|expenses):/g)
		},
		shouldOutlineRow(x) {
			return ['Account', 'Total:'].includes(x)
		},
		shouldIndentCell(x) {
			return ['Account', '<unbudgeted>'].includes(x) || this.isSubAccount(x)
		},
		async getBudget() {
			try {
				this.report = (await axios.get(this.apiUrl('budgetreport'))).data
			} catch (e) {
				showError(t('hledger', 'Error getting budget report'))
			}
		},
		async getIncomeStatement() {
			try {
				this.report = (await axios.get(this.apiUrl('incomestatement'))).data
			} catch (e) {
				showError(t('hledger', 'Error getting income statement'))
			}
		},
		async getBalanceSheet() {
			try {
				this.report = (await axios.get(this.apiUrl('balancesheet'))).data
			} catch (e) {
				showError(t('hledger', 'Error getting balance sheet'))
			}
		},
		async getAccountRegister(account) {
			try {
				const options = { params: { account } }
				const report = (await axios.get(this.apiUrl('accountregister'), options)).data
				this.report = report.map(function(row) {
					return row.concat([row[0].trim() === 'date' ? '' : 'edit'])
				})
			} catch (e) {
				showError(t('hledger', 'Error getting account ' + account + ' register'))
			}
		},
		editTransaction(row) {
			OCA.Viewer.open({ path: '/HLedger/journal.txt' })

			let attempts = 0
			const interval = setInterval(function() {
				const jqeditor = window.jQuery('#editor')
				if (jqeditor.length === 0) {
					attempts++
					if (attempts === 10) {
						clearInterval(interval)
					}
					return
				}

				clearInterval(interval)

				const editor = jqeditor.get(0)
				const textnode = window.jQuery('code', jqeditor).get(0)

				const date = row[0].replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
				const description = row[2].replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
				const pattern = '^.*' + date + '.+' + description + '.*$'
				const match = (new RegExp(pattern, 'gm')).exec(textnode.innerText)
				if (!match) {
					showError(t('hledger', 'No transaction with ' + row[0] + ' and ' + row[2]))
					return
				}

				const range = document.createRange()
				range.setStart(textnode.firstChild, match.index)
				range.setEnd(textnode.firstChild, match.index + match[0].length)

				const selection = window.getSelection()
				selection.removeAllRanges()
				selection.addRange(range)

				editor.scroll(0, range.getBoundingClientRect().top - textnode.getBoundingClientRect().top)
			}, 1000)
		},
		async saveSettings() {
			try {
				await axios.put(this.apiUrl('settings'), this.settings)
				showSuccess(t('hledger', 'HLedger settings saved'))
			} catch (e) {
				showError(t('hledger', 'Error saving settings'))
			}
		},
	},
}
</script>
