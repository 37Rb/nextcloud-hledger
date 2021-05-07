<template>
	<Content app-name="hledger">
		<AppNavigation>
			<template #list>
				<AppNavigationItem title="Balance Sheet" icon="icon-edit" @click="getBalanceSheet" />
				<AppNavigationItem title="Income Statement" icon="icon-clippy" @click="getIncomeStatement" />
				<AppNavigationItem title="Budget" icon="icon-toggle-filelist" @click="getBudget" />
				<AppNavigationItem title="Add Transactions" icon="icon-add" @click="startAddingTransactions" />
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
				<tr v-for="row in report.data" :key="row.id">
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
		<div>
			<Modal v-if="transaction.visible" title="Add Transactions" @close="stopAddingTransactions">
				<div class="hledger-add-transactions">
					<DatetimePicker v-model="transaction.date" value-type="format" />
					<select v-model="transaction.status">
						<option value="" />
						<option value="!">
							!
						</option>
						<option value="*">
							*
						</option>
					</select>
					<input v-model="transaction.code"
						type="text"
						placeholder="code">
					<input v-model="transaction.description"
						type="text"
						class="wide"
						placeholder="description">
					<input v-model="transaction.comment"
						type="text"
						class="wide"
						placeholder="comment">
					<ul class="postings">
						<li v-for="(posting, index) in transaction.postings" :key="posting.id">
							<select v-model="posting.status">
								<option value="" />
								<option value="!">
									!
								</option>
								<option value="*">
									*
								</option>
							</select>
							<input v-model="posting.account"
								type="text"
								placeholder="account">
							<input v-model="posting.amount"
								type="text"
								placeholder="amount">
							<input v-model="posting.comment"
								type="text"
								placeholder="comment">
							<button @click="removePosting(index)">
								X
							</button>
						</li>
					</ul>
					<button @click="addPosting">
						add posting
					</button>
					<button @click="addTransaction">
						add transaction
					</button>
				</div>
			</Modal>
		</div>
	</Content>
</template>
<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationSettings from '@nextcloud/vue/dist/Components/AppNavigationSettings'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import DatetimePicker from '@nextcloud/vue/dist/Components/DatetimePicker'
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
		Modal,
		DatetimePicker,
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
		startAddingTransactions() {
			this.transaction.visible = true
		},
		stopAddingTransactions() {
			this.transaction.visible = false
		},
		addPosting() {
			this.transaction.postings.push({
				status: '',
				account: '',
				amount: '',
				comment: '',
			})
		},
		removePosting(index) {
			this.transaction.postings.splice(index, 1)
		},
		async addTransaction() {
			try {
				await axios.post(this.apiUrl('transaction'), this.transaction)
				showSuccess(t('hledger', 'Transaction added'))
				this.transaction = {
					date: '',
					status: '',
					code: '',
					description: '',
					comment: '',
					postings: [],
				}
			} catch (e) {
				showError(t('hledger', 'Error adding transaction: ' + e.message))
			}
		},
		async getBudget() {
			try {
				this.report.data = (await axios.get(this.apiUrl('budgetreport'))).data
				this.report.name = 'budget'
				this.report.args = []
			} catch (e) {
				showError(t('hledger', 'Error getting budget report: ' + e.message))
			}
		},
		async getIncomeStatement() {
			try {
				this.report.data = (await axios.get(this.apiUrl('incomestatement'))).data
				this.report.name = 'incomestatement'
				this.report.args = []
			} catch (e) {
				showError(t('hledger', 'Error getting income statement: ' + e.message))
			}
		},
		async getBalanceSheet() {
			try {
				this.report.data = (await axios.get(this.apiUrl('balancesheet'))).data
				this.report.name = 'balancesheet'
				this.report.args = []
			} catch (e) {
				showError(t('hledger', 'Error getting balance sheet: ' + e.message))
			}
		},
		async getAccountRegister(account) {
			try {
				const options = { params: { account } }
				const report = (await axios.get(this.apiUrl('accountregister'), options)).data
				this.report.data = report.map(function(row) {
					return row.concat([row[0].trim() === 'date' ? '' : 'edit'])
				})
				this.report.name = 'accountregister'
				this.report.args = [account]
			} catch (e) {
				showError(t('hledger', 'Error getting account ' + account + ' register: ' + e.message))
			}
		},
		editTransaction(row) {
			const self = this
			const journalPath = '/' + this.settings.hledger_folder + '/' + this.settings.journal_file
			OCA.Viewer.open({
				path: journalPath,
				async onClose() {
					showSuccess(t('hledger', 'HLedger journal saved'))
					self.getAccountRegister(self.report.args[0])
				},
			})

			let attempts = 0
			const interval = setInterval(function() {
				const found = self.findEditorTextNode()
				if (!found) {
					attempts++
					if (attempts === 30) {
						clearInterval(interval)
						showError(t('hledger', 'Failed to open ' + journalPath))
					}
					return
				}

				clearInterval(interval)
				self.goToTransaction(found.editor, found.textnode, row[0], row[2])
			}, 1000)
		},
		findEditorTextNode() {
			const jqeditor = window.jQuery('#editor')
			if (jqeditor.length === 0) {
				return null
			}
			const jqcode = window.jQuery('code', jqeditor)
			if (jqcode.length === 0) {
				return null
			}
			return {
				editor: jqeditor.get(0),
				textnode: jqcode.get(0),
			}
		},
		goToTransaction(editor, textnode, date, description) {
			const pattern = '^.*' + this.regexEscape(date) + '.+' + this.regexEscape(description) + '.*$'
			const match = (new RegExp(pattern, 'gm')).exec(textnode.innerText)
			if (!match) {
				showError(t('hledger', 'No transaction with ' + date + ' and ' + description))
				return
			}

			const range = document.createRange()
			range.setStart(textnode.firstChild, match.index)
			range.setEnd(textnode.firstChild, match.index + match[0].length)

			const selection = window.getSelection()
			selection.removeAllRanges()
			selection.addRange(range)

			editor.scroll(0, range.getBoundingClientRect().top - textnode.getBoundingClientRect().top)
		},
		regexEscape(x) {
			return x.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
		},
		async saveSettings() {
			try {
				await axios.put(this.apiUrl('settings'), this.settings)
				showSuccess(t('hledger', 'Settings saved'))
			} catch (e) {
				showError(t('hledger', 'Error saving settings'))
			}
		},
	},
}
</script>
