<template>
	<Content app-name="hledger">
		<AppNavigation>
			<template #list>
				<AppNavigationItem title="Add Transactions" icon="icon-add" @click="startAddingTransactions" />
				<AppNavigationItem v-for="item in navigation.reports"
					:key="item.id"
					:title="item.title"
					:icon="item.icon"
					@click="getReport(item.name)" />
				<AppNavigationItem title="Edit Ledger" icon="icon-template-add" @click="openEditor()" />
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
			<v-app id="inspire">
				<v-app-bar
					class="ignorenextcloud mt-13"
					color="white"
					elevate-on-scroll
					v-if="editor.editorOpen"
					app>
					<v-spacer></v-spacer>
					<v-select
						class="pr-5"
						hide-details="auto"
						style="max-width: 175px"
						v-model="editor.selectedledger"
						:loading="editor.ledgerloading"
						label="Select Ledger File"
						@change="openEditor"
						:items="editor.availableledgers"></v-select>
					<v-menu offset-y :close-on-content-click="false">
						<template v-slot:activator="{ on, attrs }">
							<v-btn
								color="secondary"
								dark
								fab
								v-bind="attrs"
								v-on="on">
								<v-icon dark>
									mdi-filter-menu
								</v-icon>
							</v-btn>
						</template>
						<v-card class="ignorenextcloud pa-4" max-width="375">
							<v-select
								hide-details="auto"
								class="pt-0"
								label="Show most recent"
								v-model="editor.showrecentn"
								:items="[50, 100, 500, 1000]"></v-select>
							<v-combobox v-model="editor.searchfirstaccount"
								:items="editor.accounts"
								label="Filter by First Account"
								hide-details="auto"
								clearable></v-combobox>
							<v-row align="center">
								<v-col col="9">
									<v-select class="pt-0"
										style="max-width: 160px"
										label="Search Type"
										hide-details="auto"
										v-model="editor.searchtype"
										:items="['All Fields', 'Date', 'Status', 'Code', 'Description', 'Comment', 'Amount', 'Any Account', 'First Account', 'Second Account']">
									</v-select>
								</v-col>
								<v-col col="3">
									<v-btn-toggle v-model="editor.casesensitive" multiple>
										<v-btn>
											<v-icon>
												mdi-case-sensitive-alt
											</v-icon>
										</v-btn>
									</v-btn-toggle>
								</v-col>
							</v-row>
							<v-text-field clearable
								class="pt-0 pb-6"
								label="Search Text"
								v-model="searchstringraw"
								hide-details="auto"
								:loading="editor.searchstringloading"></v-text-field>
							<v-row justify="center">
								<v-date-picker
									no-title
									v-model="editor.months"
									type="month"
									class="pb-n4"
									multiple></v-date-picker>
							</v-row>
						</v-card>
					</v-menu>
					<v-menu offset-y>
						<template v-slot:activator="{ on, attrs }">
							<v-btn
								color="primary"
								dark
								fab
								v-bind="attrs"
								v-on="on">
								<v-icon dark>
									mdi-plus
								</v-icon>
							</v-btn>
						</template>
						<v-list>
							<v-list-item @click="addTransactionBlock"><v-list-item-title>Add Transaction Block</v-list-item-title></v-list-item>
							<v-list-item @click="addCommentBlock"><v-list-item-title>Add Comment</v-list-item-title></v-list-item>
							<v-list-item @click="addOtherBlock"><v-list-item-title>Add Generic Block</v-list-item-title></v-list-item>
						</v-list>
					</v-menu>
					<v-btn fab
						color="secondary"
						:loading="editor.ledgersaving"
						@click="saveLedger">
						<v-icon>mdi-content-save</v-icon>
					</v-btn>
					<v-menu offset-y :close-on-content-click="false">
						<template v-slot:activator="{ on, attrs }">
							<v-btn icon
								x-large
								v-bind="attrs"
								v-on="on">
								<v-icon>mdi-dots-vertical</v-icon>
							</v-btn>
						</template>
						<v-card class="ignorenextcloud pa-4" max-width="375">
							<v-switch
								v-model="editor.shownontransaction"
								label="Show Non-Transactions"></v-switch>
							<v-switch
								v-model="editor.showcodeinsteadofstatus"
								label="Show Code Instead of Status"></v-switch>
							<v-switch
								v-model="editor.alwaysshowcomments"
								label="Always Show Comments"></v-switch>
							<v-select label="Only Enable Editing"
								v-model="editor.onlyenable"
								multiple
								:items="['Date', 'Status', 'Code', 'Description', 'Comment', 'Amount', 'Account', 'Second Account']">
							</v-select>
						</v-card>
					</v-menu>
				</v-app-bar>
				<v-main>
					<v-container>
						<table v-if="!editor.editorOpen" class="hledger-report">
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
						<div v-if="editor.editorOpen">
							<LedgerBlock v-for="lblock in filteredLedger"
								class="ignorenextcloud"
								v-bind:key="lblock.id"
								v-bind:lblock="lblock"
								v-bind:accounts="editor.accounts"
								v-bind:alwaysshowcomments="editor.alwaysshowcomments"
								v-bind:onlyenable="editor.onlyenable"
								v-bind:showcodeinsteadofstatus="editor.showcodeinsteadofstatus"
								@change="ledgerBlockChanged"
								@delete-transaction="deleteTransaction(lblock.id)"></LedgerBlock>
						</div>
					</v-container>
				</v-main>
			</v-app>
		</AppContent>
		<div>
			<Modal v-if="transaction.visible"
				class="hl-transaction"
				title="Add Transactions"
				@close="stopAddingTransactions">
				<div class="hledger-add-transactions">
					<div class="hlt-row">
						<DatetimePicker v-model="transaction.date" value-type="format" />
						<input v-model="transaction.code"
							type="text"
							class="t-code"
							placeholder="code (transaction)">
						<select v-model="transaction.status">
							<option value="" />
							<option value="!">
								!
							</option>
							<option value="*">
								*
							</option>
						</select>
					</div>
					<div class="hlt-row hlt-wrap">
						<input v-model="transaction.description"
							type="text"
							class="t-description"
							placeholder="description (transaction)">
						<input v-model="transaction.comment"
							type="text"
							class="t-comment"
							placeholder="comment (transaction)">
					</div>
					<ul class="postings">
						<li v-for="(posting, index) in transaction.postings" :key="posting.id">
							<div class="hlt-row hlt-wrap">
								<div class="hlt-row p-left">
									<VueAutosuggest v-model="posting.account"
										:suggestions="filterAccounts(posting.account)"
										:input-props="{id:'p'+index+'__input', class:'p-account', placeholder:'account'}"
										@selected="(suggestion) => accountSelected(index, suggestion.item)">
										<template slot-scope="{suggestion}">
											<span>{{ suggestion.item }}</span>
										</template>
									</VueAutosuggest>
									<input v-model="posting.amount"
										type="text"
										class="p-amount"
										placeholder="amount">
								</div>
								<div class="hlt-row p-right">
									<select v-model="posting.status">
										<option value="" />
										<option value="!">
											!
										</option>
										<option value="*">
											*
										</option>
									</select>
									<input v-model="posting.comment"
										type="text"
										class="p-comment"
										placeholder="comment (posting)">
									<button v-if="index > 1" @click="removePosting(index)">
										X
									</button>
								</div>
							</div>
						</li>
					</ul>
					<button @click="addPosting">
						Add Posting
					</button>
					<button :disabled="cantBalanceTransaction" @click="balanceTransaction">
						Balance
					</button>
					<button :disabled="transactionInvalid" @click="addTransaction">
						Save Transaction
					</button>
					<div class="t-validation">
						{{ transactionInvalid }}
					</div>
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
import moment from '@nextcloud/moment'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { VueAutosuggest } from 'vue-autosuggest'
import LedgerBlock from './LedgerBlock.vue'
import { toLedger, fromLedger } from './ledgerparser2.js'
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
		VueAutosuggest,
		LedgerBlock,
	},
	data() {
		const state = OCP.InitialState.loadState('hledger', 'state')
		this.initializeTransaction(state)
		this.initializeEditor(state)
		return state
	},
	async mounted() {
		/* const availableLedgers = (await axios.get(this.apiUrl('availableledgers'))).data
		this.editor.availableledgers = availableLedgers
		for (let i = 0; i < this.editor.availableledgers.length; i++)
		{
		} */
	},
	computed: {
		transactionInvalid() {
			if (!this.transaction.code && !this.transaction.description) {
				return 'Transaction code or description required'
			}
			let sum = 0
			const units = []
			for (let i = 0; i < this.transaction.postings.length; i++) {
				const posting = this.transaction.postings[i]
				if (!posting.account) {
					return 'Posting ' + (i + 1) + ' account required'
				}
				const amount = this.parseAmount(posting.amount)
				if (!amount) {
					return 'Posting ' + (i + 1) + ' numeric amount required'
				}
				sum += amount[0]
				const unit = (amount.length > 1) ? amount[1] : ''
				if (!units.includes(unit)) {
					units.push(unit)
				}
			}
			if (units.length < 2 && Math.abs(sum) > 0.001) {
				return 'Transaction does not balance: ' + sum.toFixed(2)
			}
			return null
		},
		cantBalanceTransaction() {
			let amountsEntered = 0
			let amountsBlank = 0
			const units = []
			for (let i = 0; i < this.transaction.postings.length; i++) {
				const posting = this.transaction.postings[i]
				const amount = this.parseAmount(posting.amount)
				if (posting.amount.trim() === '') {
					amountsBlank++
				} else if (amount) {
					amountsEntered++
					const unit = amount.length > 1 ? amount[1] : ''
					if (!units.includes(unit)) {
						units.push(unit)
					}
				}
			}
			return (units.length > 1) || (amountsBlank !== 1) || (amountsEntered !== this.transaction.postings.length - 1)
		},
		filteredLedger() {
			return this.$options.filters.filterBlocks(this.editor.ledger, this.editor.months, this.editor.shownontransaction, this.searchMatches, this.editor.searchfirstaccount, this.editor.searchtype, this.editor.searchstring, this.editor.casesensitive.includes(0), moment, this.editor.showrecentn)
		},
		searchstringraw: {
			get() {
				return this.searchString
			},
			set(val) {
				this.editor.searchstringloading = true
				if (this.editor.searchstringtimeout) {
					clearTimeout(this.editor.searchstringtimeout)
				}
				this.editor.searchstringtimeout = setTimeout(() => {
					this.editor.searchstringloading = false
					this.editor.searchstring = val
				}, 750)
			}
		}
	},
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
			this.initializeTransaction(this)
			this.transaction.visible = true
		},
		stopAddingTransactions() {
			this.transaction.visible = false
			this.reloadReport()
		},
		addPosting() {
			this.transaction.postings.push(this.newPosting())
		},
		removePosting(index) {
			this.transaction.postings.splice(index, 1)
		},
		parseAmount(entered) {
			const split = entered.trim().split(/\s+/)
			if (!entered || isNaN(split[0])) {
				return null
			}
			const parsed = [parseFloat(split[0])]
			if (split.length > 1) {
				parsed.push(split[1])
			}
			return parsed
		},
		balanceTransaction() {
			let sum = 0
			let emptyAmountIndex = null
			for (let i = 0; i < this.transaction.postings.length; i++) {
				const posting = this.transaction.postings[i]
				const amount = this.parseAmount(posting.amount)
				if (posting.amount.trim() === '') {
					emptyAmountIndex = i
				} else if (amount) {
					sum += amount[0]
				}
			}
			if (emptyAmountIndex !== null) {
				this.transaction.postings[emptyAmountIndex].amount = (-sum).toFixed(2)
			}
		},
		async addTransaction() {
			try {
				await axios.post(this.apiUrl('transaction'), this.transaction)
				const snippet = this.truncate((this.transaction.code + ' ' + this.transaction.description).trim(), 20)
				showSuccess(t('hledger', 'Saved ' + snippet))
				this.updateNewAccounts(this)
				this.initializeTransaction(this)
			} catch (e) {
				showError(t('hledger', 'Error saving transaction: ' + e.message))
			}
		},
		updateNewAccounts(state) {
			for (let i = 0; i < state.transaction.postings.length; i++) {
				const posting = state.transaction.postings[i]
				if (!state.accounts.includes(posting.account)) {
					state.accounts.push(posting.account)
				}
			}
		},
		initializeTransaction(state) {
			if (!('transaction' in state)) {
				state.transaction = {
					visible: false,
					date: new Date().toISOString().split('T')[0],
					postings: [],
				}
			}
			state.transaction.status = ''
			state.transaction.code = ''
			state.transaction.description = ''
			state.transaction.comment = ''
			state.transaction.postings.splice(0, state.transaction.postings.length)
			state.transaction.postings.push(this.newPosting())
			state.transaction.postings.push(this.newPosting())
		},
		initializeEditor(state) {
			if (!('editor' in state)) {
				state.editor = {
				}
			}
			state.editor.editorOpen = false
			state.editor.ledgerloading = false
			state.editor.ledgersaving = false
			state.editor.accounts = []
			state.editor.ledger = []
			state.editor.shownontransaction = true
			state.editor.showcodeinsteadofstatus = false
			state.editor.alwaysshowcomments = false
			state.editor.onlyenable = []
			state.editor.showrecentn = 100
			state.editor.months = []
			state.editor.searchtype = 'All Fields'
			state.editor.searchstring = ''
			state.editor.casesensitive = []
			state.editor.searchfirstaccount = ''
			state.editor.searchstringtimeout = null
			state.editor.searchstringloading = false
		},
		newPosting() {
			return {
				status: '',
				account: '',
				amount: '',
				comment: '',
			}
		},
		filterAccounts(input) {
			return [{
				data: this.accounts.filter(account => {
					return input && account.toLowerCase().includes(input.toLowerCase())
				}),
			}]
		},
		accountSelected(posting, account) {
			this.transaction.postings[posting].account = account
		},
		reloadReport() {
			if (this.report.name === 'accountregister') {
				this.getAccountRegister(this.report.args[0])
			} else if (this.report.name) {
				this.getReport(this.report.name)
			} else {
				this.getReport('balancesheet')
			}
		},
		async getReport(report) {
			try {
				this.report.data = (await axios.get(this.apiUrl(report))).data
				this.report.name = report
				this.report.args = []
				this.editor.editorOpen = false
			} catch (e) {
				showError(t('hledger', 'Error getting ' + report + ': ' + e.message))
			}
		},
		async openEditor() {
			this.editor.ledger = []
			this.editor.accounts = []
			this.editor.ledgerloading = true
			try {
				const loadedTextLedger = (await axios.get(this.apiUrl('loadledgercontents'), { params: { fileName: this.editor.selectedledger } })).data
				const loadedLedger = fromLedger(loadedTextLedger)
				const backConvertedLedger = toLedger(loadedLedger).replace(/\s+/g, ' ')
				const strippedLoadedTextLedger = loadedTextLedger.replace(/\s+/g, ' ')
				if (backConvertedLedger !== strippedLoadedTextLedger) {
					showError(t('hledger', 'Error: Ledger did not backconvert to the same ledger text. This means that there is something in the ledger file that this editor does not support'))
				} else {
					for (let i = 0; i < loadedLedger.blocks.length; i++) {
						loadedLedger.blocks[i].id = i
					}
					this.editor.ledger = loadedLedger.blocks
					this.editor.accounts = loadedLedger.accounts
					this.editor.editorOpen = true
					this.editor.ledgerloading = false
				}
			} catch (e) {
				showError(t('hledger', 'Error getting ledger contents: ' + e.message))
				this.editor.ledgerloading = false
			}
		},
		async saveLedger() {
			this.editor.ledgersaving = true
			try {
				const currentLedger = { blocks: this.editor.ledger }
				const serializedLedger = toLedger(currentLedger)
				await axios.post(this.apiUrl('saveledgercontents'), { fileName: this.editor.selectedledger, contents: serializedLedger })
				this.editor.ledgersaving = false
			} catch (e) {
				showError(t('hledger', 'Error saving ledger contents: ' + e.message))
				this.editor.ledgersaving = false
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
				const self = this
				setTimeout(function() { self.scrollToBottom('html') }, 37)
			} catch (e) {
				showError(t('hledger', 'Error getting account ' + account + ' register: ' + e.message))
			}
		},
		scrollToBottom(selector) {
			const main = window.jQuery(selector)
			main.scrollTop(main.prop('scrollHeight'))
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
				const found = self.findEditorElements()
				if (!found) {
					attempts++
					if (attempts === 30) {
						clearInterval(interval)
						showError(t('hledger', 'Failed to open ' + journalPath))
					}
					return
				}

				clearInterval(interval)
				self.goToTransaction(found.editor, found.code, row[0], row[2])
			}, 1000)
		},
		findEditorElements() {
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
				code: jqcode.get(0),
			}
		},
		goToTransaction(editor, code, date, description) {
			const textnode = this.findTextNodeInElement(code)
			if (!textnode) {
				showError(t('hledger', 'Selecting transaction, can\'t find text node in editor.'))
				return
			}

			const pattern = '^.*' + this.regexEscape(date) + '.+' + this.regexEscape(description) + '.*$'
			const match = (new RegExp(pattern, 'gm')).exec(textnode.wholeText)
			if (!match) {
				showError(t('hledger', 'No transaction with ' + date + ' and ' + description))
				return
			}

			const range = document.createRange()
			range.setStart(textnode, match.index)
			range.setEnd(textnode, match.index + match[0].length)

			const selection = window.getSelection()
			selection.removeAllRanges()
			selection.addRange(range)

			editor.scroll(0, range.getBoundingClientRect().top - code.getBoundingClientRect().top)
		},
		findTextNodeInElement(element) {
			for (let i = 0; i < element.childNodes.length; i++) {
				const child = element.childNodes[i]
				if (child.nodeType === Node.TEXT_NODE) {
					return child
				} else if (child.nodeType === Node.ELEMENT_NODE) {
					const found = this.findTextNodeInElement(child)
					if (found) {
						return found
					}
				}
			}
			return null
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
		ledgerBlockChanged(val) {
			for (let i = 0; i < this.editor.ledger.length; i++) {
				if (this.editor.ledger[i].id === val.id) {
					/* Do a one-level deep copy */
					for (const prop in val) {
						if (typeof val[prop] === 'object') {
							this.editor.ledger[i][prop] = JSON.parse(JSON.stringify(val[prop]))
						} else {
							this.editor.ledger[i][prop] = val[prop]
						}
					}
					return
				}
			}
			alert('Could not find changed transaction in master list')
		},
		deleteTransaction(id) {
			for (let i = 0; i < this.editor.ledger.length; i++) {
				if (this.editor.ledger[i].id === id) {
					this.editor.ledger.splice(i, 1)
					return
				}
			}
			alert('Could not find transaction in master list')
		},
		searchMatches(block, searchFirstAccount, searchType, searchString, caseSensitive) {
			/* Sometimes this ends up as 'null' if the clear button is used. Deal with it. */
			if (!searchString) {
				searchString = ''
			}
			if (!caseSensitive) {
				searchString = searchString.toLocaleLowerCase()
			}
			/* Omit checking for 'All Fields' since that will be handled by the 'Any Account' block below */
			if (searchFirstAccount || searchType === 'First Account') {
				if ((block.type === 'transaction' || block.type === 'other') && block.postingIndexes.length > 0) {
					if (searchFirstAccount && block.lines[block.postingIndexes[0]].account !== searchFirstAccount) {
						// searchFirstAccount is considered an 'AND' operation. If it fails, return false.
						return false
					}
					if (searchType === 'First Account') {
						if (caseSensitive) {
							if (block.lines[block.postingIndexes[0]].account.indexOf(searchString) >= 0) {
								return true
							}
						} else {
							if (block.lines[block.postingIndexes[0]].account.toLocaleLowerCase().indexOf(searchString) >= 0) {
								return true
							}
						}
					}
				} else if (searchFirstAccount) {
					/* If we are using searchFirstAccount and this is not a transaction or the transaction has no postings, return false */
					return false
				}
			}
			// If 'All Fields' is being used but no search string is provided, just match everything
			if (searchType === 'All Fields' && !searchString) {
				return true
			}
			/* Omit checking for 'All Fields' since that will be handled by the 'Any Account' block below */
			if (searchType === 'Second Account') {
				if ((block.type === 'transaction' || block.type === 'other') && block.postingIndexes.length > 0) {
					if (caseSensitive && block.lines[block.postingIndexes[1]].account.indexOf(searchString) >= 0) {
						return true
					} else if (!caseSensitive && block.lines[block.postingIndexes[1]].account.toLocaleLowerCase().indexOf(searchString) >= 0) {
						return true
					}
				}
			}
			if (searchType === 'Any Account' || searchType === 'All Fields') {
				if (block.type === 'transaction' || block.type === 'other') {
					for (let i = 0; i < block.postingIndexes.length; i++) {
						if (caseSensitive && block.lines[block.postingIndexes[i]].account.indexOf(searchString) >= 0) {
							return true
						} else if (!caseSensitive && block.lines[block.postingIndexes[i]].account.toLocaleLowerCase().indexOf(searchString) >= 0) {
							return true
						}
					}
				}
			}
			if (searchType === 'Date' || searchType === 'All Fields') {
				if (block.type === 'transaction') {
					if (caseSensitive && block.date.indexOf(searchString) >= 0) {
						return true
					} else if (!caseSensitive && block.date.toLocaleLowerCase().indexOf(searchString) >= 0) {
						return true
					}
				}
			}
			if (searchType === 'Status' || searchType === 'All Fields') {
				if (block.type === 'transaction') {
					if (block.status === searchString) {
						return true
					}
				}
			}
			if (searchType === 'Code' || searchType === 'All Fields') {
				if (block.type === 'transaction') {
					if (caseSensitive && block.code.indexOf(searchString) >= 0) {
						return true
					} else if (!caseSensitive && block.code.toLocaleLowerCase().indexOf(searchString) >= 0) {
						return true
					}
				}
			}
			if (searchType === 'Description' || searchType === 'All Fields') {
				if (block.type === 'transaction') {
					if (caseSensitive && block.description.indexOf(searchString) >= 0) {
						return true
					} else if (!caseSensitive && block.description.toLocaleLowerCase().indexOf(searchString) >= 0) {
						return true
					}
				}
			}
			if (searchType === 'Comment' || searchType === 'All Fields') {
				if (block.type === 'transaction') {
					if (caseSensitive && block.comment.indexOf(searchString) >= 0) {
						return true
					} else if (!caseSensitive && block.comment.toLocaleLowerCase().indexOf(searchString) >= 0) {
						return true
					}
				}
				if (block.type === 'transaction' || block.type === 'other') {
					for (let i = 0; i < block.lines.length; i++) {
						let testString = ''
						if (block.lines[i].type === 'posting') {
							testString = block.lines[i].comment
						} else if (block.lines[i].type === 'comment') {
							testString = block.lines[i].text
						}
						if (caseSensitive && testString.indexOf(searchString) >= 0) {
							return true
						} else if (!caseSensitive && testString.toLocaleLowerCase().indexOf(searchString) >= 0) {
							return true
						}
					}
				} else if (block.type === 'comment') {
					if (caseSensitive && block.text.indexOf(searchString) >= 0) {
						return true
					} else if (!caseSensitive && block.text.toLocaleLowerCase().indexOf(searchString) >= 0) {
						return true
					}
				}
			}
			if (searchType === 'Amount' || searchType === 'All Fields') {
				if (block.type === 'transaction' || block.type === 'other') {
					for (let i = 0; i < block.lines.length; i++) {
						if (block.lines[i].type === 'posting') {
							if (caseSensitive && block.lines[i].amount.indexOf(searchString) >= 0) {
								return true
							} else if (!caseSensitive && block.lines[i].amount.toLocaleLowerCase().indexOf(searchString) >= 0) {
								return true
							}
						}
					}
				}
			}
			return false
		},
		scrollToBottom2() {
			window.scrollTo(0, document.body.scrollHeight || document.documentElement.scrollHeight)
		},
		addTransactionBlock() {
			let lastId = -1
			if (this.editor.ledger.length > 0) {
				lastId = this.editor.ledger[this.editor.ledger.length - 1].id
			}
			this.editor.ledger.push({ id: lastId + 1, type: 'transaction', lines: [], postingIndexes: [], date: moment().format('YYYY-MM-DD'), status: '', code: '', description: '', comment: '' })
			setTimeout(this.scrollToBottom2, 10) // Scroll after slight delay so that the scrolling happens after the GUI has added the new elements
		},
		addCommentBlock() {
			let lastId = -1
			if (this.editor.ledger.length > 0) {
				lastId = this.editor.ledger[this.editor.ledger.length - 1].id
			}
			this.editor.ledger.push({ id: lastId + 1, type: 'comment', text: '', commentType: ';' })
			setTimeout(this.scrollToBottom2, 10) // Scroll after slight delay so that the scrolling happens after the GUI has added the new elements
		},
		addOtherBlock() {
			let lastId = -1
			if (this.editor.ledger.length > 0) {
				lastId = this.editor.ledger[this.editor.ledger.length - 1].id
			}
			this.editor.ledger.push({ id: lastId + 1, type: 'other', text: '<Replace this with ledger entry>', lines: [], postingIndexes: [] })
			setTimeout(this.scrollToBottom2, 10) // Scroll after slight delay so that the scrolling happens after the GUI has added the new elements
		},
	},
	filters: {
		filterBlocks(blockList, months, shownontransaction, searchMatches, searchFirstAccount, searchType, searchString, caseSensitive, moment, showRecentN) {
			const filteredList = []
			const momentMonths = []
			for (let j = 0; j < months.length; j++) {
				momentMonths.push(moment(months[j], 'YYYY-MM'))
			}
			for (let i = 0; i < blockList.length; i++) {
				if (blockList[i].type === 'transaction') {
					if (momentMonths.length <= 0) {
						if (searchMatches(blockList[i], searchFirstAccount, searchType, searchString, caseSensitive)) {
							filteredList.push(blockList[i])
						}
					} else {
						const thisDate = moment(blockList[i].date, 'YYYY-MM-DD')
						for (let j = 0; j < momentMonths.length; j++) {
							if (thisDate.year() === momentMonths[j].year() && thisDate.month() === momentMonths[j].month()) {
								if (searchMatches(blockList[i], searchFirstAccount, searchType, searchString, caseSensitive)) {
									filteredList.push(blockList[i])
								}
								break
							}
						}
					}
				} else if (shownontransaction) {
					if (searchMatches(blockList[i], searchFirstAccount, searchType, searchString, caseSensitive)) {
						filteredList.push(blockList[i])
					}
				}
			}
			if (filteredList.length > showRecentN) {
				filteredList.splice(0, filteredList.length - showRecentN)
			}
			return filteredList
		}
	},
}
</script>
<style>
	.ignorenextcloud input, .ignorenextcloud textarea, .ignorenextcloud input:disabled, .ignorenextcloud textarea:disabled {
		border: inherit;
		background-color: inherit;
		font-size: inherit;
		margin-bottom: inherit;
		color: inherit;
		opacity: inherit;
	}

	.ignorenextcloud div.v-input:not(.v-text-field--filled) input {
		margin-top: inherit;
	}

	.ignorenextcloud input:active, .ignorenextcloud textarea:active, .ignorenextcloud textarea:hover, .ignorenextcloud textarea:focus {
		background-color: inherit !important;
	}

	.ignorenextcloud select, .ignorenextcloud input, .ignorenextcloud textarea {
		/* width: inherit; */
		min-height: inherit;
	}

	.ignorenextcloud button {
		padding: inherit;
		min-height: inherit;
	}

	.ignorenextcloud label {
		cursor: inherit;
	}

	.ignorenextcloud tbody tr:hover, .ignorenextcloud tbody tr:focus, .ignorenextcloud tbody tr:active {
		background-color: inherit;
	}
</style>
